<?php

namespace App\Admin\Organisation;

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
use Sonata\FormatterBundle\Form\Type\FormatterType;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints\Valid;

class RoleAdmin extends BaseAdmin
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
        $collection->add('contentEdit', $this->getRouterIdParameter() . '/edit-content');
        $collection->add('publish', $this->getRouterIdParameter() . '/publish');
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
            ->addIdentifier('foundedOn', null, ['label' => 'form.label_founded_on'])
            ->addIdentifier('type', null, ['label' => 'form.label_type'])
            ->addIdentifier('address', null, ['label' => 'form.label_address'])
            ->addIdentifier('name', null, ['label' => 'form.label_name'])
            ->addIdentifier('registrationNumber', null, ['label' => 'form.label_registration_number'])
            ->addIdentifier('logoName', null, ['label' => 'form.label_logo_name'])
            ->addIdentifier('code', null, ['label' => 'form.label_code'])
            ->addIdentifier('subdomain', null, ['label' => 'form.label_subdomain'])
            ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General', ['class' => 'col-md-7'])->end()
//            ->with('Description', ['class' => 'col-md-7'])->end()
        ;


        $formMapper
            ->with('General')
            ->add('name')
            ;
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
            ->add('subdomain', null, ['label' => 'form.label_subdomain'])
            ;
    }


}
