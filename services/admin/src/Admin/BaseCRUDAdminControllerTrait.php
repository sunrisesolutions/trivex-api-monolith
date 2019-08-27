<?php

namespace App\Admin;

use App\Entity\Messaging\Message;
use App\Entity\User\User;
use App\Security\DecisionMakingInterface;
use App\Service\User\UserService;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bridge\Twig\Command\DebugCommand;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

trait BaseCRUDAdminControllerTrait
{

    protected $templateRegistry;

    protected function getTemplateRegistry()
    {
        $this->templateRegistry = $this->container->get($this->admin->getCode().'.template_registry');
        if (!$this->templateRegistry instanceof TemplateRegistryInterface) {
            throw new \RuntimeException(sprintf(
                'Unable to find the template registry related to the current admin (%s)',
                $this->admin->getCode()
            ));
        }

        return $this->templateRegistry;
    }

    protected function getRefererParams()
    {
        $request = $this->getRequest();
        $referer = $request->headers->get('referer');
        $baseUrl = $request->getBaseUrl();
        if (empty($baseUrl)) {
            return null;
        }
        $lastPath = substr($referer, strpos($referer, $baseUrl) + strlen($baseUrl));

        return $this->get('router')->match($lastPath);
//		getMatcher()
    }

    protected function isAdmin()
    {
        return $this->get(UserService::class)->getUser()->isAdmin();
    }

    /**
     * Sets the admin form theme to form view. Used for compatibility between Symfony versions.
     *
     * @param FormView $formView
     * @param string $theme
     */
    protected function setFormTheme(FormView $formView, $theme)
    {
        $twig = $this->get('twig');

        // BC for Symfony < 3.2 where this runtime does not exists
        if (!method_exists(AppVariable::class, 'getToken')) {
            $twig->getExtension(FormExtension::class)
                ->renderer->setTheme($formView, $theme);

            return;
        }

        // BC for Symfony < 3.4 where runtime should be TwigRenderer
        if (!method_exists(DebugCommand::class, 'getLoaderPaths')) {
            $twig->getRuntime(TwigRenderer::class)->setTheme($formView, $theme);

            return;
        }
        $twig->getRuntime(FormRenderer::class)->setTheme($formView, $theme);

    }


    /**
     * Show action.
     *
     * @param int|string|null $id
     *
     * @return Response
     * @throws AccessDeniedException If access is not granted
     *
     * @throws NotFoundHttpException If the object does not exist
     */
    public function decideAction(
        $id = null, $action = 'show'
    )
    {
        $request = $this->getRequest();
        $id = $request->get($this->admin->getIdParameter());

        /** @var DecisionMakingInterface $object */
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s', $id));
        }

        if (!in_array(get_class($object), [Message::class])) {
            throw new AccessDeniedException(sprintf('unable to find the object with id: %s', $id));
        }

        $this->admin->checkAccess($action, $object);

        $preResponse = $this->preShow($request, $object);
        if (null !== $preResponse) {
            return $preResponse;
        }

        $this->admin->setSubject($object);

        // NEXT_MAJOR: Remove this line and use commented line below it instead
        $template = $this->getTemplateRegistry()->getTemplate('decide');

        //		$template = $this->templateRegistry->getTemplate('show');

        if ($request->isMethod('post')) {
            $decision = $this->getDecision($action);

            $object->setDecisionRemarks($request->get('decision-remarks'));
            if (!empty($decision)) {
                $object->makeDecision($decision);
                $this->admin->update($object);
            }
        }

        if (!empty($res = $this->preRenderDecision($action, $object))) {
            return $res;
        }

        return $this->renderWithExtraParams($template, [
            'action' => $action,
            'object' => $object,
            'elements' => $this->admin->getShow(),
        ], null);
    }

    protected function preRenderDecision($action, $object)
    {
        if ('show' !== $action) {
            return $this->redirect($this->admin->generateObjectUrl('decide', $object, ['action' => 'show']));
        }
    }
}
