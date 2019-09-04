<?php

namespace App;

use App\Admin\BaseAdmin;
use App\Admin\BaseCRUDAdminController;
use App\Doctrine\Module\ORMEventSubscriber;
use App\Service\Organisation\OrganisationService;
use App\Service\User\UserService;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        ///////        <<< MY CUSTOM DOCTRINE EVENT SUBSCRIBERS ///////
//        Not @stof, but no. The EventSubscriber interface is from Doctrine\Common and is valid for ORM, ODM and possibly a bunch of other object mappers. Adding the autoconfiguration you posted would also register event subscribers for other mappers in ORM which is not desirable.
//    The only option you can do this is by introducing a ORM-specific interface.
        $container->registerForAutoconfiguration(ORMEventSubscriber::class)
            ->addTag('doctrine.event_subscriber');
        //////         >>> MY CUSTOM DOCTRINE EVENT SUBSCRIBERS //////

        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir().'/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');



        /////// <<<  MY CUSTOM ADMIN SERVICE AUTOCONFIG ///////
        $definitions = [];
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, CRUDController::class)) {
                if ($container->hasDefinition($class)) {
                    $container->getDefinition($class)->addTag('controller.service_arguments');
                }
            } elseif (is_subclass_of($class, BaseAdmin::class)) {
                if (empty($class::AUTO_CONFIG)) {
                    continue;
                }
                $className = explode('\\', str_replace('Admin', '', $class));

                $def = new Definition();
                $def->setClass($class);
                $def->addTag('sonata.admin', [
                    'manager_type' => 'orm',
                    'label' => strtolower(end($className)),
                    'label_translator_strategy' => 'sonata.admin.label.strategy.underscore'
                ]);
                $def->addMethodCall('setTemplate', [ 'decide', 'CRUD/decide.html.twig' ]);
                if(empty($code = $class::ADMIN_CODE)) {
                    $code = $class;
                }
                $code = $class;
                if (empty($entity = $class::ENTITY)) {
                    $entity = str_replace('Admin\\', 'Entity\\', $code);
                    $entity = str_replace('AdminBundle', 'ModelBundle', $entity);
                    $entity = str_replace('Admin', '', $entity);
                }

                if (empty($controller = $class::CONTROLLER)) {
                    $controller = $class.'Controller';
                    if (!class_exists($controller)) {
                        $controller = BaseCRUDAdminController::class;
                    }
                }

                if (!empty($templates = $class::TEMPLATES)) {
                    foreach ($templates as $name => $template) {
                        $def->addMethodCall('setTemplate', [$name, $template]);
                    }
                }

                $userService = $container->getDefinition(UserService::class);
                $organisationService = $container->getDefinition(OrganisationService::class);
                $def->setArguments([$code, $entity, $controller, $userService, $organisationService]);

                $definitions[$code] = $def;
            }
        }

        $container->addDefinitions($definitions);
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, CRUDController::class)) {
            } elseif (is_subclass_of($class, BaseAdmin::class)) {
                if (empty($class::AUTO_CONFIG)) {
                    continue;
                }
                $className = explode('\\', str_replace('Admin', '', $class));
                $def = $container->getDefinition($class);
                if (!empty($children = $class::CHILDREN)) {
                    foreach ($children as $child => $property) {
                        $def->addMethodCall('addChild', [$container->getDefinition($child), $property]);
                    }
                }
            }
        }
        /////// >>>  MY CUSTOM ADMIN SERVICE AUTOCONFIG ///////
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
    }
}
