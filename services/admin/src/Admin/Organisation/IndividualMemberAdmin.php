<?php

namespace App\Admin\Organisation;

use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Person;
use App\Entity\Organisation\Role;
use App\Entity\Organisation\Organisation;
use App\Entity\User\OrganisationUser;
use App\Util\Organisation\AppUtil;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Knp\Menu\ItemInterface as MenuItemInterface;
use App\Admin\BaseAdmin;
use App\Entity\User\User;
use App\Service\User\UserService;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use App\Service\User\UserManager;
use App\Service\User\UserManagerInterface;
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
            ->add('person.phoneNumber', null, ['label' => 'form.label_telephone'])
            ->add('person.nationality.nricNumber', TextType::class, ['label' => 'form.label_nric_number'])
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
            ->add('person.birthDate', DatePickerType::class, [
                'label' => 'form.label_birth_date',
                'format' => 'dd-MM-yyyy',
                'placeholder' => 'dd-mm-yyyy',
                'datepicker_use_button' => false,
            ])
//            ->add('person')
//            ->add('createdAt', DateTimePickerType::class, ['label' => 'form.label_created_at'])

        ;
        $formMapper->end();
        $formMapper->with('Profession');
        $formMapper->add('person.interestGroups');

        $formMapper->end();
        $formMapper
            ->with('Account');

        $formMapper
            ->add('person.email', null, ['label' => 'form.label_email'])
            ->add('person.password', null, ['label' => 'form.label_password']);

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
        $oPerson = $object->getPerson();
        $organisation = $object->getOrganisation();

        $container = $this->getContainer();
        $manager = $container->get('doctrine.orm.default_entity_manager');
        if (empty($oPerson->getId())) {
            if (empty($oPerson->getEmail())) {
                $oPerson->setEmail($oPerson->getPhoneNumber().'@magenta-wellness.com');
            }
            $fopRepo = $manager->getRepository(Person::class);
            /** @var Person $foPerson */
            $foPerson = $fopRepo->findOneBy(['email' => $oPerson->getEmail(),
            ]);
            if (empty($foPerson)) {
                $foPerson = $fopRepo->findOneBy(['phoneNumber' => $oPerson->getPhoneNumber(),
                ]);
            }
            if (!empty($foPerson)) {
                $oPerson->removeIndividualMember($object);
                $foPerson->addIndividualMember($object);
            }
        }

        // update Person
        $email = $oPerson->getEmail();
        $phone = $oPerson->getPhoneNumber();
        $pRepo = $manager->getRepository(\App\Entity\Person\Person::class);
        $fPerson = null;
        if (!empty($nricNumber = $oPerson->getNationality()->getNricNumber())) {
            $fPersons = $pRepo->findByNricNumber($nricNumber);
            if (count($fPersons) > 0) {
                $fPerson = $fPersons[0];
                /** @var \App\Entity\Person\Person $_fPerson */
                foreach ($fPersons as $_fPerson) {
                    if ($_fPerson->getEmail() === $email) {
                        $fPerson = $_fPerson;
                    }
                }
            }
        }

        if (empty($fPerson) && !empty($email)) {
            /** @var \App\Entity\Person\Person $fPerson */
            $fPerson = $pRepo->findOneBy(['email' => $email,
            ]);
        }
        if (!empty($fPerson)) {
            AppUtil::copyObjectScalarProperties($fPerson, $oPerson);
        } else {
            $fPerson = new \App\Entity\Person\Person();
            AppUtil::copyObjectScalarProperties($oPerson, $fPerson);
            $manager->persist($fPerson);
            $manager->flush($fPerson);
            AppUtil::copyObjectScalarProperties($fPerson, $oPerson);
        }

        $oPerson->setUuid($fPerson->getUuid());

        // update User person
        $upRepo = $manager->getRepository(\App\Entity\User\Person::class);
        /** @var \App\Entity\User\Person $fuPerson */
        $fuPerson = $upRepo->findOneBy(['uuid' => $fPerson->getUuid(),
        ]);
        if (empty($fuPerson) && !empty($email)) {
            $fuPerson = $upRepo->findOneBy(['email' => $email,
            ]);
        }
        if (empty($fuPerson)) {
            $fuPerson = $upRepo->findOneBy(['phoneNumber' => $phone,
            ]);
        }
        if (empty($fuPerson)) {
            $fuPerson = new \App\Entity\User\Person();
            AppUtil::copyObjectScalarProperties($oPerson, $fuPerson);
        }
        $fuPerson->setUuid($fPerson->getUuid());
        $manager->persist($fuPerson);
        $manager->flush($fuPerson);

        // update User user
//        if (!empty($plainPassword = $oPerson->getPassword()) && !empty($oPerson->getEmail())) {
        if (empty($plainPassword = $oPerson->getPassword()) && empty($oPerson->getId())) {
            $plainPassword = 'p@ssword!@#$%^';
            $oPerson->setPassword($plainPassword);
        }

        if (empty($user = $fuPerson->getUser())) {
            if (empty($oPerson->getEmail())) {
                $oPerson->setEmail($oPerson->getPhoneNumber().'@magenta-wellness.com');
            }
            $user = $manager->getRepository(User::class)->findOneBy(['email' => $oPerson->getEmail()]);

//                $user = $fuPerson->getUser();
            if (empty($user)) {
                if (empty($oPerson->getEmail())) {
                    $oPerson->setEmail($oPerson->getPhoneNumber().'@magenta-wellness.com');
                }
                $user = $manager->getRepository(User::class)->findOneBy(['username' => $oPerson->getEmail()]);
            }
            if (empty($user)) {
                $user = new  User();
                $user->setEmail($email);
                $user->setUsername($email);
            }
            $fuPerson->setUser($user);

        }
        $user->setIdNumber($oPerson->getNationality()->getNricNumber());
        $user->setBirthDate($oPerson->getBirthDate());
        $user->setPhone($oPerson->getPhoneNumber());

        $user->setPerson($fuPerson);
        $user->setPlainPassword($plainPassword);
        $manager->persist($fuPerson);
        $manager->persist($user);
        $manager->flush($fuPerson);
        $manager->flush($user);

        $fPerson->setUserUuid($user->getUuid());
        $manager->persist($fPerson);
        $manager->flush($fPerson);
//        }
        $oPerson->setPassword(null);


        // update NRIC
        $oNationality = $oPerson->getNationality();
        if (!empty($personUuid = $oPerson->getUuid())) {
//            $fPerson = $manager->getRepository(\App\Entity\Person\Person::class)->findOneBy(['uuid' => $personUuid]);
            if (empty($fPerson)) {
                $fPerson = new \App\Entity\Person\Person();
                AppUtil::copyObjectScalarProperties($oPerson, $fPerson);
                $fPerson->setUuid('');
                $manager->persist($fPerson);
                $manager->flush($fPerson);
                $oPerson->setUuid($fPerson->getUuid());
            }
            $fNationality = $fPerson->getNationality();
            if (!empty($fNationality)) {
                AppUtil::copyObjectScalarProperties($oNationality, $fNationality, false);
                AppUtil::copyObjectScalarProperties($fNationality, $oNationality);
            } else {
                $fNationality = $fPerson->createNationality($oNationality->getCountry(), $oNationality->getNricNumber(), $oNationality->getPassportNumber());
            }
            $manager->persist($fNationality);
            $manager->flush($fNationality);
            $oNationality->setUuid($fNationality->getUuid());
        }

        // update Message


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
     * @param User $object
     */
    public function preUpdate($object)
    {
        parent::preUpdate($object);
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
        $oPerson = $object->getPerson();
        $manager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $oRoles = $object->getRoles();

        // update User
        /** @var \App\Entity\User\Person $uPerson */
        $uPerson = $manager->getRepository(\App\Entity\User\Person::class)->findOneBy(['uuid' => $oPerson->getUuid()]);
        $user = $uPerson->getUser();
        $ous = $user->getOrganisationUsers();
        $organisation = $object->getOrganisation();

        /** @var OrganisationUser $uMember */
        $uMember = null;
        /** @var OrganisationUser $ou */
        foreach ($ous as $ou) {
            if ($ou->getOrganisation()->getUuid() === $organisation->getUuid()) {
                $uMember = $ou;
                break;
            }
        }

        if (empty($uMember)) {
            /** @var \App\Entity\User\Organisation $uOrganisation */
            $uOrganisation = $manager->getRepository(\App\Entity\User\Organisation::class)->findOneBy(['uuid' => $organisation->getUuid()]);
            if (empty($uOrganisation)) {
                $uOrganisation = new \App\Entity\User\Organisation();
                AppUtil::copyObjectScalarProperties($organisation, $uOrganisation);
            }
            $uMember = new OrganisationUser();
            $uMember->setOrganisation($uOrganisation);
            $manager->persist($uOrganisation);
            $manager->flush($uOrganisation);
        }

        $uMember->setUser($user);
        $uMember->setUuid($object->getUuid());
        $roles = $object->getRoles();
        $roleArrays = [];
        /** @var Role $role */
        foreach ($roles as $role) {
            $roleArrays[] = $role->getName();
        }
        $uMember->setRoles($roleArrays);

        $manager->persist($uMember);
        $manager->flush($uMember);


        // update Message

        // update Event
        $eMember = $manager->getRepository(\App\Entity\Event\IndividualMember::class)->findOneBy(['uuid' => $object->getUuid()]);
        $ePerson = $manager->getRepository(\App\Entity\Event\Person::class)->findOneBy(['uuid' => $oPerson->getUuid()]);

        if (empty($ePerson)) {
            $ePerson = new \App\Entity\Event\Person();
        }

        AppUtil::copyObjectScalarProperties($oPerson, $ePerson);

        if (empty($eMember)) {
            $eMember = new \App\Entity\Event\IndividualMember();
        }
        $eMember->setUuid($object->getUuid());
        $eMember->setPerson($ePerson);
        $ePerson->addIndividualMember($eMember);
        $manager->persist($ePerson);
        $manager->persist($eMember);

        $manager->flush();
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

