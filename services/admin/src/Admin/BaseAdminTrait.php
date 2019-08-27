<?php

namespace App\Admin;

use App\Entity\User\OrganisationUser;
use Bean\Component\Organization\IoC\OrganizationAwareInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use App\Admin\Organisation\OrganisationAdmin;
use App\Entity\Media\Media;
use App\Entity\Person\Person;
use App\Service\ServiceContext;
use App\Entity\Organisation\IndividualGroup;
use App\Entity\Organisation\Organisation;
use App\Entity\Organisation\IndividualMember;
use App\Security\DecisionMakingInterface;

use App\Entity\User\User;
use App\Service\Organisation\OrganisationService;
use App\Service\User\UserService;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait BaseAdminTrait
{
    private $isAdmin;
    /**
     * @var User
     */
    private $user;
    protected
        $action = '';
    protected
        $actionParams = [];

    /** @return ContainerInterface */
    protected function getContainer()
    {
        return $this->getConfigurationPool()->getContainer();
    }

    protected function getOrganisationClass()
    {
        return Organisation::class;
    }

    protected function getTemplateType($name)
    {
        $_name = strtoupper($name);
        if ($_name === 'EDIT') {
            if (empty($subject = $this->getSubject()) || empty($subject->getId())) {
                return 'CREATE';
            } else {
                return 'EDIT';
            }
        }

        return $_name;
    }

    /**
     * @param string $name
     *
     * @return null|string
     * @deprecated since 3.34, will be dropped in 4.0. Use TemplateRegistry services instead
     *
     */
    public function getTemplate($name)
    {
        return $this->getTemplateRegistry()->getTemplate($name);
    }

    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        parent::configureDatagridFilters($filter);
        if (is_subclass_of($this->getClass(), FullTextSearchInterface::class)) {
            $filter->add('fullText', null, [
                'label' => 'form.label_full_text_search',
                'show_filter' => true
            ]);
        }
    }

    public function getPersistentParameters()
    {
        $parameters = parent::getPersistentParameters();
        if (!$this->hasRequest()) {
            return $parameters;
        }
        if (!empty($org = $this->getRequest()->get('organisation'))) {
            return array_merge($parameters, array(
                'organisation' => $org
            ));
        }

        return $parameters;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        parent::configureRoutes($collection);
        $collection->add('decide', $this->getRouterIdParameter().'/decide/{action}');
    }

    protected function buildShow()
    {
        parent::buildShow();
        /** @var FieldDescription $fieldDescription */
        foreach ($this->showFieldDescriptions as $fieldDescription) {
            switch ($fieldDescription->getMappingType()) {
//				case ClassMetadata::MANY_TO_ONE:
//					$fieldDescription->setTemplate(
//						'@SonataAdmin/CRUD/Association/list_many_to_one.html.twig'
//					);
//
//					break;
                case ClassMetadata::ONE_TO_ONE:
                    $fieldDescription->setTemplate(
                        'CRUD/Association/show_one_to_one.html.twig'
                    );

                    break;
                case ClassMetadata::ONE_TO_MANY:
                    $fieldDescription->setTemplate(
                        'CRUD/Association/show_one_to_many.html.twig'
                    );
                    break;
//				case ClassMetadata::MANY_TO_MANY:
//					$fieldDescription->setTemplate(
//						'@SonataAdmin/CRUD/Association/list_many_to_many.html.twig'
//					);
//
//					break;
            }
        }
    }

    protected
    function buildList()
    {
        parent::buildList();
        /** @var FieldDescription $fieldDescription */
        foreach ($this->listFieldDescriptions as $fieldDescription) {
            switch ($fieldDescription->getMappingType()) {
                case ClassMetadata::MANY_TO_ONE:
                    $fieldDescription->setTemplate(
                        'CRUD/Association/list_many_to_one.html.twig'
                    );
                    break;
                case ClassMetadata::ONE_TO_ONE:
                    $fieldDescription->setTemplate(
                        'CRUD/Association/list_one_to_one.html.twig'
                    );

                    break;
                case ClassMetadata::ONE_TO_MANY:
                    $fieldDescription->setTemplate(
                        'CRUD/Association/list_one_to_many.html.twig'
                    );
                    break;
//				case ClassMetadata::MANY_TO_MANY:
//					$fieldDescription->setTemplate(
//						'@SonataAdmin/CRUD/Association/list_many_to_many.html.twig'
//					);
//
//					break;
            }
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getExportFormats()
    {
        return [
            'xls'
        ];
    }

    public function generateUrl($name, array $parameters = array(), $absolute = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $c = $this->getConfigurationPool()->getContainer();
        if (!empty($orgId = $c->get('request_stack')->getCurrentRequest()->query->get('organisation', 0))) {
            $org = $c->get('doctrine')->getRepository(Organisation::class)->find($orgId);
            if (!empty($org)) {
                $parameters['organisation'] = $orgId;
            }
        }

        return parent::generateUrl($name, $parameters, $absolute);
    }

    protected function getCurrentOrganisationFromAncestors(BaseAdmin $parent = null)
    {
        return $this->organisationService->getCurrentOrganisationFromAncestors($parent);
    }

    protected function getCurrentIndividualMember($required = false)
    {
        $user = $this->getLoggedInUser();

        if (empty($org = $this->getCurrentOrganisation($required))) {
            return null;
        }
        $ous = $user->getOrganisationUsers();
        $imUuid = null;
        /** @var OrganisationUser $ou */
        foreach ($ous as $ou) {
            if ($ou->getOrganisation()->getUuid() === $org->getUuid()) {
                $imUuid = $ou->getUuid();
            }
        }
        if (empty($imUuid)) {
            return null;
        }
        return $this->getContainer()->get('doctrine.orm.default_entity_manager')->getRepository(IndividualMember::class)->findOneBy(['uuid' => $imUuid,
        ]);
    }

    /**
     * @return mixed|null
     */
    public
    function getCurrentOrganisation(
        $required = true
    )
    {
        $context = new ServiceContext();
        $context->setType(ServiceContext::TYPE_ADMIN_CLASS);
        $context->setAttribute('parent', $this->getParent());

        /** @var OrganisationService $orgService */
        $orgService = $this->organisationService;
        return $orgService->getCurrentOrganisation($context, $required, $this->getOrganisationClass());
    }

    /**
     * @return User
     */
    protected
    function getLoggedInUser()
    {
        if ($this->user === null) {
            $this->user = $this->userService->getUser();
        }

        return $this->user;
    }

    protected
    function isAdmin()
    {
        if ($this->isAdmin === null) {
            $this->isAdmin = $this->getConfigurationPool()->getContainer()->get('security.authorization_checker')->isGranted('ROLE_ADMIN');
        }

        return $this->isAdmin;
    }


    public
    function getAction()
    {
        if (empty($this->action)) {
            $request = $this->getRequest();
            if (!empty($action = $request->query->get('action'))) {

                $this->action = $action;

            }
        }

        return $this->action;
    }

    public
    function getActionParam(
        $key
    )
    {
        if (array_key_exists($key, $this->actionParams)) {
            return $this->actionParams[$key];
        }

        return null;
    }

    /**
     * @return array
     */
    public
    function getActionParams()
    {
        return $this->actionParams;
    }

    /**
     * @param array $actionParams
     */
    public
    function setActionParams(
        $actionParams
    )
    {
        $this->actionParams = $actionParams;
    }

    public
    function setAction(
        $action
    )
    {
        $this->action = $action;
    }

    public
    function toString(
        $object
    )
    {
        if (method_exists($object, 'getTitle')) {
            return $object->getTitle();
        } elseif (method_exists($object, 'getName')) {
            return $object->getName();
        }

        return parent::toString($object);
    }

    public
    function getRequest()
    {
        if (!$this->request) {
//            throw new \RuntimeException('The Request object has not been set');
            $this->request = $this->getConfigurationPool()->getContainer()->get('request_stack')->getCurrentRequest();
        }

        return $this->request;
    }

    protected function getAccess()
    {
        return array_merge(parent::getAccess(), [
            'decide' => 'DECIDE',
            'decide_everything' => 'DECIDE_ALL',
            'approve' => 'DECISION_'.DecisionMakingInterface::DECISION_APPROVE,
            'reject' => 'DECISION_'.DecisionMakingInterface::DECISION_REJECT,
            'reset' => 'DECISION_'.DecisionMakingInterface::DECISION_RESET
        ]);
    }

    public
    function isGranted(
        $name, $object = null
    )
    {
        /** @var ContainerInterface $container */
        $container = $this->getConfigurationPool()->getContainer();
//        if ($container->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
//        }

        /** @var UserService $userService */
        $userService = $this->userService;
        $user = $userService->getUser();
        $isAdmin = $container->get('security.authorization_checker')->isGranted('ROLE_ADMIN');

//        $pos = $container->get(UserService::class)->getPosition();
        if ($isAdmin) {
            return !empty($this->getCurrentOrganisation(false));

//            return in_array($this->getClass(), [Organisation::class, Person::class, Media::class]);

            if (is_array($name)) {
                foreach ($name as $action) {
                    $_name = strtoupper($action);
                }
            } else {
                $_name = strtoupper($name);
            }
            if (in_array($_name, ['LIST', 'EDIT', 'DELETE', 'CREATE', 'VIEW', 'EXPORT'])) {
                if ($_name === 'CREATE') {
                    return !empty($this->getCurrentOrganisation(false));
                }

                return true;
            } elseif (substr($_name, 0, 5) === 'ROLE_') {
                return parent::isGranted($name, $object);
            }

        }

        $org = $this->getCurrentOrganisation(false);
        $member = $this->getCurrentIndividualMember(false);
        if (is_array($name)) {
            $isGranted = true;
            foreach ($name as $action) {
//                $_isGranted = $user->isGranted($action, $object, $this->getClass(), $member, $org);
//                $isGranted = $isGranted && $_isGranted;
            }

//            return $isGranted;
        }

//        return $user->isGranted($name, $object, $this->getClass(), $member, $org);


        return parent::isGranted($name, $object);
    }

    protected
    function getFilterByOrganisationQueryForModel(
        $class
    )
    {
        /** @var ProxyQuery $productQuery */
        $brandQuery = $this->getModelManager()->createQuery($class);
        /** @var Expr $expr */
        $expr = $brandQuery->expr();
        $orgFieldName = $this->getOrganisationFieldName($class);
        $brandQuery->andWhere($expr->eq('o.'.$orgFieldName, $this->getCurrentOrganisation()->getId()));

        return $brandQuery;
    }

    protected function getOrganisationFieldName($class)
    {
//        if (in_array($class, [IndividualMember::class, IndividualGroup::class])) {
//            return 'organization';
//        }

        return 'organisation';
    }

    public
    function createQuery(
        $context = 'list'
    )
    {
        $query = parent::createQuery($context);
        $parentFD = $this->getParentFieldDescription();
        if ($this->isAdmin()) {
//			if($this->getRequest()->attributes->get('_route') !== 'sonata_admin_retrieve_autocomplete_items') {
            // admin should see everything except in embeded forms
            if (in_array($this->getClass(), [
                    Organisation::class,
                    User::class,
                    Person::class
                ]) || !empty($parentFD) && $parentFD->getType() !== ModelAutocompleteType::class) {
                return $query;
            }
        }

        $organisation = $this->getCurrentOrganisation();

        if (!empty($organisation)) { // && ! empty($organisation)
            $classname = $this->getClass();
            $reflection = new \ReflectionObject(new $classname);
            if ($reflection->hasProperty('organisation')) {
//                $orgProp = $reflection->getProperty('organisation');
//                if (!empty($orgProp)) {
                $this->filterQueryByOrganisation($query, $organisation);
//                }
            }
        } else {
            // TODO: change this so that 1 person can manage multiple organisations
            $this->clearResults($query);
        }

        return $query;
//        $query->andWhere()
    }

    protected
    function filterQueryByOrganisation(
        ProxyQuery $query, $organisation
    )
    {
        $pool = $this->getConfigurationPool();
        $request = $this->getRequest();
        $container = $pool->getContainer();
        /** @var Expr $expr */
        $expr = $query->getQueryBuilder()->expr();
        $orgFieldName = $this->getOrganisationFieldName($this->getClass());
        $orgAlias = $query->entityJoin([['fieldName' => $orgFieldName]]);

        return $query->andWhere($expr->eq($orgAlias.'.uuid', $expr->literal($organisation->getUuid())));
    }

    /**
     * @param ProxyQuery $query
     *
     * @return ProxyQuery
     */
    protected
    function clearResults(
        ProxyQuery $query
    )
    {
        /** @var Expr $expr */
        $expr = $query->getQueryBuilder()->expr();
        $query->andWhere($expr->eq($expr->literal(true), $expr->literal(false)));

        return $query;
    }

    protected
    function verifyDirectParent(
        $parent
    )
    {
    }

    protected
    function isDirectParentAccess(
        $parentClass, $subjectAdminCodes = array()
    )
    {
        $parentAdmin = $this->getParent();
        $isDirectParentAccess = false;
        if (!empty($parentAdmin)) {
            $_parentClass = $parentAdmin->getClass();
            $isDirectParentAccess = $parentClass === $_parentClass;
            if (!empty($subjectAdminCodes)) {
                $isDirectParentAccess = $isDirectParentAccess && (in_array($this->getCode(), $subjectAdminCodes));
            }
        }

        return $isDirectParentAccess;
    }

    protected
    function isAppendFormElement()
    {
        $request = $this->getRequest();

        return $request->attributes->get('_route') === 'sonata_admin_append_form_element';
    }

    protected
    function filterByParentClass(
        ProxyQuery $query, $parentClass, $subjectAdminCodes = array()
    )
    {
        $pool = $this->getConfigurationPool();
        $request = $this->getRequest();
        $container = $pool->getContainer();
        /** @var Expr $expr */
        $expr = $query->getQueryBuilder()->expr();

        $isDirectParentAccess = $this->isDirectParentAccess($parentClass, $subjectAdminCodes);
        $parentAdmin = $this->getParent();
        $rootAlias = $query->getRootAliases()[0];
        if ($isDirectParentAccess) {
            if ($this->verifyDirectParent($parentAdmin->getSubject())) {
                $query->andWhere($expr->eq($rootAlias.'.'.$this->getParentAssociationMapping(), $parentAdmin->getSubject()->getId()));

                return $query;
            };
        } else {
            if ($this->isAppendFormElement()) {
                // Indirect Parent
                $code = $request->query->get('code');
                $objectId = $request->query->get('objectId');

                if (in_array($code, $subjectAdminCodes)) {
                    /** @var AdminInterface $childAdmin */
                    $childAdmin = $pool->getAdminByAdminCode($code);
                    $child = $this->getModelManager()->find($childAdmin->getClass(), $objectId);
                    $indirectParentAssociationMapping = $childAdmin->getParentAssociationMapping();
                    $parentGetter = 'get'.ucfirst($indirectParentAssociationMapping);
                    $indirectParent = $child->{$parentGetter}();
//                    definitely null =>
//                    $indirectParentAdmin = $childAdmin->getParent();
                    if (get_class($indirectParent) === $parentClass) {
                        $query->andWhere($expr->eq($rootAlias.'.'.$childAdmin->getParentAssociationMapping(), $indirectParent->getId()));

                        return $query;
                    }
                }

            } else {
                // Indirect Parent but with direct access NOT Ajax call
                $childAdmin = $pool->getAdminByAdminCode($request->attributes->get('_sonata_admin'));
                $query->andWhere($expr->eq($rootAlias.'.'.$childAdmin->getParentAssociationMapping(), $request->attributes->get('id')));

                return $query;
            }
        }

        return $this->clearResults($query);
    }

    public
    function getSystemModules()
    {
        $registry = $this->getConfigurationPool()->getContainer()->get('doctrine');
        $modules = $registry->getRepository(SystemModule::class)->findAll();

        return $modules;
    }

    /**
     * @param mixed $object
     */
    public
    function preValidate(
        $object
    )
    {
        $classname = $this->getClass();
        $reflection = new \ReflectionObject(new $classname);
        if ($reflection->hasProperty('organisation')) {
//            $orgProp = $reflection->getProperty('organisation');
//            if (!empty($orgProp)) {
            $object->setOrganisation($this->getCurrentOrganisation());
//            }
        }
    }

    public function preUpdate($object)
    {
        parent::preUpdate($object);
        if (method_exists($object, 'setUpdatedAt')) {
            $object->setUpdatedAt(new \DateTime());
        }
    }
}
