<?php

namespace App\Admin\Organisation;

use App\Entity\Organisation\Organisation;
use App\Util\User\AppUtil;
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
use Sonata\AdminBundle\Form\FormMapper;

use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\Form\Type\DatePickerType;
use Sonata\FormatterBundle\Form\Type\FormatterType;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints\Valid;

class OrganisationAdmin extends BaseAdmin
{
    const CHILDREN = [IndividualMemberAdmin::class => 'organisation'];
    const ADMIN_CODE = 'OrganisationAdmin';
    const TEMPLATES = [
        'list' => 'Admin/Organisation/Organisation/CRUD/list.html.twig',
        'edit' => 'Admin/Organisation/Organisation/CRUD/edit.html.twig',
    ];

    protected $action;

    protected $datagridValues = array(
        // display the first page (default = 1)
//        '_page' => 1,
        // reverse order (default = 'ASC')
        '_sort_order' => 'DESC',
        // name of the ordered field (default = the model's id field, if any)
        '_sort_by' => 'updatedAt',
    );

    public function getOrganisation()
    {
        return $this->subject;
    }

    public function getCurrentChapter()
    {
        return null;
    }

    public function getNewInstance()
    {
        /** @var User $object */
        $object = parent::getNewInstance();

        return $object;
    }

    public function isGranted($name, $object = null)
    {
        /** @var ContainerInterface $container */
        $container = $this->getConfigurationPool()->getContainer();
        if ($container->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            return true;
        }
        return parent::isGranted($name, $object);
    }

    public function toString($object)
    {
        return $object instanceof Organisation
            ? $object->getName()
            : 'Organisation'; // shown in the breadcrumb on the create view
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
//        $collection->add('contentEdit', $this->getRouterIdParameter() . '/edit-content');
        $collection->remove('show');
        $collection->add('editCurrentOrganisation', 'edit-current-organisation');
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
//            ->addIdentifier('foundedOn', null, ['label' => 'form.label_founded_on'])
//            ->add('logoName', null, [
//                'label' => 'form.label_logo_name',
//                'template'=>'Admin/Organisation/Organisation/Action/list__action__impersonate.html.twig'
//            ])
            ->addIdentifier('name', null, ['label' => 'form.label_name'])
//            ->addIdentifier('registrationNumber', null, ['label' => 'form.label_registration_number'])
//            ->add('type', null, ['label' => 'form.label_type'])
            ->add('code', null, ['label' => 'form.label_code'])
            ->add('address', null, ['label' => 'form.label_address'])
            ->add('subdomain', null, ['label' => 'form.label_subdomain'])//        templates/Admin/Organisation/IndividualMember/Action/list__action__impersonate.html.twig
        ;

        $listMapper->add('_action', 'actions', [
                'actions' => [
//					'impersonate' => array( 'template' => 'admin/user/list__action__impersonate.html.twig' ),
                    'impersontate' => ['template' => 'Admin/Organisation/Organisation/Action/list__action__impersonate.html.twig'],
                    'edit' => [],
                    'delete' => [],

//                ,
//                    'view_description' => array('template' => '::admin/product/description.html.twig')
//                ,
//                    'view_tos' => array('template' => '::admin/product/tos.html.twig')
                ],
            ]
        );
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General', ['class' => 'col-md-7'])->end()//            ->with('Description', ['class' => 'col-md-7'])->end()
        ;

        $formMapper
            ->with('General')
            ->add('logoName', null, [
                'required' => false,
                'label' => false])
            ->add('foundedOn', DatePickerType::class, [
                'required' => false,
                'format' => 'dd-MM-yyyy',
                'placeholder' => 'dd-mm-yyyy',
                'datepicker_use_button' => false,
            ])
//            ->add('type')
            ->add('address', null, ['required' => false])
            ->add('name')
            ->add('registrationNumber')
//            ->add('logoName')
            ->add('code')
            ->add('subdomain')
            ->add('freeonMessagingEnabled');
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
     * @param User $object
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

    private function postUpdateEntity(Organisation $organisation)
    {
        $manager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        // update Messaging


        $manager->flush();
    }

    public function postPersist($object)
    {
        parent::postPersist($object);
        $this->postUpdateEntity($object);
    }

    public function postUpdate($object)
    {
        parent::postUpdate($object);
        $this->postUpdateEntity($object);
    }

    ///////////////////////////////////
    ///
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
            ->add('name', null, ['label' => 'form.label_name'])
            ->add('foundedOn', null, ['label' => 'form.label_uuid'])
            ->add('type', null, ['label' => 'form.label_type'])
            ->add('address', null, ['label' => 'form.label_address'])
            ->add('registrationNumber', null, ['label' => 'form.label_registration_number'])
            ->add('logoName', null, ['label' => 'form.label_logo_name'])
            ->add('code', null, ['label' => 'form.label_code'])
            ->add('subdomain', null, ['label' => 'form.label_subdomain']);
    }


}
