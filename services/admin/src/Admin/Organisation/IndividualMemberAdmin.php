<?php

namespace App\Admin\Organisation;

use App\Entity\Organisation\IndividualMember;
use App\Entity\Person\Person;
use App\Entity\Organisation\Role;
use App\Entity\Organisation\Organisation;
use App\Repository\Person\PersonRepository;
use App\Util\AppUtil;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Knp\Menu\ItemInterface as MenuItemInterface;
use App\Admin\BaseAdmin;
use App\Entity\User\User;
use App\Service\User\UserService;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\ChoiceList\ModelChoiceLoader;
use Sonata\AdminBundle\Form\FormMapper;

use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\Form\Type\DatePickerType;
use Sonata\Form\Type\DateTimePickerType;
use Sonata\FormatterBundle\Form\Type\FormatterType;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraints\Valid;

class IndividualMemberAdmin extends BaseAdmin
{

    const CHILDREN = [];

    protected $action;

    protected $datagridValues = array(
        // display the first page (default = 1)
//        '_page' => 1,
        // reverse order (default = 'ASC')
        '_sort_order' => 'DESC',
        // name of the ordered field (default = the model's id field, if any)
        '_sort_by' => 'updatedAt',
    );

    public function getIndividualMember()
    {
        return $this->subject;
    }

    public function getCurrentChapter()
    {
        return null;
    }

    public function getNewInstance()
    {
        /** @var IndividualMember $object */
        $object = parent::getNewInstance();
        if (empty($person = $object->getPerson())) {
            $object->setPerson($person = new Person());
            if (empty($user = $person->getUser())) {
                $person->setUser(new User());
            }
        }

        return $object;
    }

    public function toString($object)
    {
        return $object instanceof IndividualMember
            ? $object->getPerson()->getName()
            : 'Members'; // shown in the breadcrumb on the create view
    }

    public function createQuery($context = 'list')
    {
        /** @var ProxyQueryInterface $query */
        $query = parent::createQuery($context);
        if (empty($this->getParentFieldDescription())) {
//            $this->filterQueryByPosition($query, 'position', '', '');
        }

//        $query->andWhere()

        return $query;
    }

    public function configureRoutes(RouteCollection $collection)
    {
        parent::configureRoutes($collection);
        $collection->add('contentEdit', $this->getRouterIdParameter().'/edit-content');
        $collection->add('publish', $this->getRouterIdParameter().'/publish');
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {

    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('person.name', null, ['label' => 'form.label_name'])
            ->add('person.email', null, ['label' => 'form.label_email'])
            ->add('person.phoneNumber', null, ['label' => 'form.label_telephone'])
            ->add('roles', null, [
                'label' => 'form.label_roles',
                'associated_property' => 'nameTrans'])
            ->add('createdAt', null, ['label' => 'form.label_created_at']);
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General', ['class' => 'col-md-4'])->end()
            ->with('Profession', ['class' => 'col-md-4'])->end()
            ->with('Account', ['class' => 'col-md-4'])->end();
        $this->getFilterByOrganisationQueryForModel(Role::class);
//        $propertyAccessor = $this->getConfigurationPool()->getContainer()->get('access');
        $formMapper
            ->with('General')
            ->add('person.givenName', null, ['label' => 'form.label_given_name'])
//            ->add('person.middleName', null, ['label' => 'form.label_middle_name'])
            ->add('person.familyName', null, ['label' => 'form.label_family_name'])
            ->add('person.alternateName', null, ['label' => 'form.label_alternate_name'])
            ->add('person.phoneNumber', null, ['label' => 'form.label_telephone'])
            ->add('person.nationality.nricNumber', TextType::class, [
                'label' => 'form.label_nric_number'])
            ->add('person.birthDate', DatePickerType::class, [
                'label' => 'form.label_birth_date',
                'format' => 'dd-MM-yyyy',
                'placeholder' => 'dd-mm-yyyy',
                'datepicker_use_button' => false,
            ])
            ->add('person.gender', ChoiceType::class, [
                'required' => false,
                'label' => 'form.label_gender',
                'multiple' => false,
                'placeholder' => 'Select Gender',
                'choices' => [
                    'MALE' => 'MALE',
                    'FEMALE' => 'FEMALE'
                ],
                'translation_domain' => $this->translationDomain,
            ])
//            ->add('person')
//            ->add('createdAt', DateTimePickerType::class, ['label' => 'form.label_created_at'])

        ;
        $formMapper->end();
        $formMapper->with('Profession');
        $formMapper
            ->add('groupName', TextType::class,
                [
                    'required' => false,
                    'label' => 'form.label_group_name'
                ])
            ->add('person.interestGroups', null,
                [
                    'required' => false,
                    'label' => 'form.label_interest_groups'
                ]);
        $formMapper
            ->add('person.jobTitle', null,
                [
                    'required' => false,
                    'label' => 'form.label_job_title'
                ]
            )
            ->add('person.jobIndustry', null,
                [
                    'required' => false,
                    'label' => 'form.label_job_industry'
                ]
            )
            ->add('person.employerName', null,
                [
                    'required' => false,
                    'label' => 'form.label_employer_name'
                ]);

        $formMapper->end();
        $formMapper
            ->with('Account');

        $formMapper
            ->add('email', null, ['label' => 'form.label_email'])
            ->add('person.user.plainPassword', PasswordType::class, [
                'required' => false,
                'label' => 'form.label_password']);

        $formMapper
            ->add('roles', ModelType::class, [
                'required' => false,
                'multiple' => true,
                'property' => 'nameTrans',
                'btn_add' => false,
                'query' => $this->getFilterByOrganisationQueryForModel(Role::class)
            ]);

        $formMapper->end();
    }

    protected function configureTabMenu(MenuItemInterface $menu, $action, AdminInterface $childAdmin = null)
    {
        parent::configureTabMenu($menu, $action, $childAdmin);
//        if (!empty($this->subject) && !empty($this->subject->getId())) {
//            $menu->addChild('Manage Content', [
//                'uri' => $this->getConfigurationPool()->getContainer()->get('router')->generate('admin_magenta_cbookmodel_book_book_show', ['id' => $this->getSubject()->getId()])
//            ]);
//        }
    }

    /**
     * @param IndividualMember $object
     */
    public function preValidate($object)
    {
        parent::preValidate($object);
        $person = $object->getPerson();
        if (empty($person->getEmail())) {
            $person->setEmail($email = $person->getNationality()->getNricNumber().'@magenta-wellness.com');
        }
        $user = $person->getUser();
        if (empty($user->getEmail())) {
            $user->setEmail($email);
        }
    }

    /**
     * @param IndividualMember $object
     */
    public function prePersist($object)
    {
        parent::prePersist($object);
//        if (!$object->isEnabled()) {
//            $object->setEnabled(true);
//        }
    }

    /**
     * @param IndividualMember $object
     */
    public function preUpdate($object)
    {
        parent::preUpdate($object);

        $object->setUpdatedAt(new \DateTime());
        $person = $object->getPerson();
        $person->setUpdatedAt(new \DateTime());
        $nationality = $person->getNationality();
        $nationality->setUpdatedAt(new \DateTime());
        $user = $person->getUser();
        $user->setUpdatedAt(new \DateTime());

        $container = $this->getContainer();
        $manager = $container->get('doctrine.orm.default_entity_manager');
        /** @var PersonRepository $personRepo */
        $personRepo = $container->get('doctrine')->getRepository(Person::class);

        $personWithEmailExisting = false;
        if (!empty($email = $object->getEmail())) {
            $personWithEmail = $personRepo->findOneBy(['email' => $email,
            ]);
            if (!empty($personWithEmail)) {
                $person->removeIndividualMember($object);
                $personWithEmail->addIndividualMember($object);
                $object->setPerson($personWithEmail);
                $manager->persist($person);
                $personWithEmail->preSave();
                $manager->persist($personWithEmail);

                if (!empty($userWithPersonEmail = $personWithEmail->getUser())) {
                    $userWithPersonEmail->setUpdatedAt(new \DateTime());
                    $manager->persist($userWithPersonEmail);
                }

                $personWithEmailExisting = true;
            }
        }

        if (!$personWithEmailExisting) {
            if (!empty($nric = $person->getNationality()->getNricNumber())) {
                $persons = $personRepo->findByNricNumber($nric);
                if (count($persons) > 0) {
                    /** @var Person $personWithNric */
                    $personWithNric = $persons[0];
                    $person->removeIndividualMember($object);
                    $personWithNric->addIndividualMember($object);
                    $object->setPerson($personWithNric);
                    $personWithNric->setEmail($email);
                    $personWithNric->preSave();
                    $manager->persist($personWithNric);
                    if (!empty($userWithPersonNric = $personWithNric->getUser())) {
                        $userWithPersonNric->setUpdatedAt(new \DateTime());
                        $manager->persist($userWithPersonNric);
                    }
                }
                $person->setEmail($email);
                $manager->persist($person);
            }
        }
        $manager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $person->preSave();
        $manager->persist($person);
        $manager->persist($user);
        $manager->persist($nationality);


//        if (!$object->isEnabled()) {
//            $object->setEnabled(true);
//        }
    }

    public function postPersist($object)
    {
        parent::postPersist($object); // TODO: Change the autogenerated stub
        $this->postUpdateEntity($object);
    }

    public function postUpdate($object)
    {
        parent::postUpdate($object); // TODO: Change the autogenerated stub
        $this->postUpdateEntity($object);
    }

    public function postUpdateEntity(IndividualMember $object)
    {

    }

    ///////////////////////////////////
    ///

    ///
    ///////////////////////////////////
    /**
     * @var UserManagerInterface
     */
    protected $userManager;

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filterMapper)
    {
        $filterMapper
            ->add('id')
            ->add('uuid', null, ['label' => 'form.label_uuid'])
            ->add('accessToken', null, ['label' => 'form.label_access_token'])
            ->add('createdAt', null, ['label' => 'form.label_created_at']);
    }


}

