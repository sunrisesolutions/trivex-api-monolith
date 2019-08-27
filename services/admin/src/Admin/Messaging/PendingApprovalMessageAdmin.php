<?php

namespace App\Admin\Messaging;

use App\Entity\Messaging\Message;
use App\Entity\Messaging\Organisation;
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
use Sonata\AdminBundle\Form\FormMapper;

use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\FormatterBundle\Form\Type\FormatterType;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints\Valid;

class PendingApprovalMessageAdmin extends BaseAdmin
{
    const ORGANISATION_CLASS = Organisation::class;

    const ENTITY = Message::class;

    const CHILDREN = [];

    protected $baseRouteName = 'pending_approval_message';

    protected $baseRoutePattern = 'messaging-message/pending-approval';

    protected $action;

    protected $datagridValues = array(
        // display the first page (default = 1)
//        '_page' => 1,
        // reverse order (default = 'ASC')
        '_sort_order' => 'DESC',
        // name of the ordered field (default = the model's id field, if any)
        '_sort_by' => 'createdAt',
    );

    public function getCurrentChapter()
    {
        return null;
    }

    public function getNewInstance()
    {
        /** @var Message $object */
        $object = parent::getNewInstance();

        return $object;
    }

    public function toString($object)
    {
        return $object instanceof Person
            ? $object->getName()
            : 'Person'; // shown in the breadcrumb on the create view
    }

    public function createQuery($context = 'list')
    {
        /** @var ProxyQueryInterface $query */
        $query = parent::createQuery($context);
        if (empty($this->getParentFieldDescription())) {

        }

        /** @var Expr $expr */
        $expr = $query->getQueryBuilder()->expr();
        $query->andWhere($expr->like('o.status', $expr->literal(Message::STATUS_PENDING_APPROVAL)));



//        $query->andWhere()

        return $query;
    }

    public function configureRoutes(RouteCollection $collection)
    {
        parent::configureRoutes($collection);
        $collection->remove('create');
        $collection->remove('edit');
//        $collection->add('contentEdit', $this->getRouterIdParameter() . '/approve');
//        $collection->add('publish', $this->getRouterIdParameter() . '/publish');
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {

    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper->add('_action', 'actions', [
                'label' => 'form.label_action',
                'actions' => array(
//					'impersonate' => array( 'template' => 'admin/user/list__action__impersonate.html.twig' ),
//                    'edit' => array(),
                    'approve' => array( 'template' => 'Admin/Messaging/PendingApprovalMessage/Action/list__action__approve.html.twig' ),
                    'delete' => array(),

//                ,
//                    'view_description' => array('template' => '::admin/product/description.html.twig')
//                ,
//                    'view_tos' => array('template' => '::admin/product/tos.html.twig')
                )
            ]
        );
        $listMapper
            ->add('subject', null, ['label' => 'form.label_subject'])
            ->add('body', null, ['label' => 'form.label_body'])
            ->add('status', null, ['label' => 'form.label_status']);



        $listMapper->add('createdAt', null, ['label' => 'form.label_created_at']);


//		$listMapper->add('positions', null, [ 'template' => '::admin/user/list__field_positions.html.twig' ]);
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General', ['class' => 'col-md-7'])->end()
//            ->with('Description', ['class' => 'col-md-7'])->end()
        ;

        $formMapper
            ->with('General')
//                ->add('username')
            ->add('subject', null, ['label' => 'form.label_family_name'])
//                ->add('admin')
        ;
        $formMapper->end();

//		$formMapper->with('Description');
//		$formMapper->add('text', CKEditorType::class, [ 'required' => false, 'label' => false ]);
//		$formMapper->end();

//		$formMapper->with('Content');
//		$formMapper->add('text', CKEditorType::class, [
//		]);
//		$formMapper->add('text', SimpleFormatterType::class, [
//			'format' => 'richhtml',
//			'ckeditor_context' => 'default',
//			'ckeditor_image_format' => 'big',
//		]);
//		$formMapper->end();

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
     * @param Message $object
     */
    public function prePersist($object)
    {
        parent::prePersist($object);
    }

    /**
     * @param Message $object
     */
    public function preUpdate($object)
    {
        parent::preUpdate($object);

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
            ->add('sender.person.name')
            ->add('subject')
            ->add('body')
        ;
//			->add('groups')
//		;
    }


}
