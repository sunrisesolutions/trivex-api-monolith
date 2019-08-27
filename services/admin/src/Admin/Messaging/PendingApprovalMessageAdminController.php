<?php

namespace App\Admin\Messaging;

use App\Admin\BaseCRUDAdminController;
use App\Entity\Messaging\Message;
use App\Security\DecisionMakingInterface;
use App\Service\Organisation\OrganisationService;
use App\Entity\Person\Person;
use App\Entity\Classification\Category;
use App\Entity\Classification\CategoryItem\PersonCategoryItem;
use App\Service\ServiceContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PendingApprovalMessageAdminController extends BaseCRUDAdminController
{

    /** @var PendingApprovalMessageAdmin $admin */
    protected $admin;

    /**
     * @param Person $object
     *
     * @return RedirectResponse
     */
    protected function redirectTo($object)
    {
        $request = $this->getRequest();

        if (null !== $request->get('btn_create_and_edit')) {
            return new RedirectResponse($this->admin->generateUrl('show', ['id' => $object->getId()]));
        }

        return parent::redirectTo($object);
    }

    public function createAction()
    {
        return parent::createAction();
    }

    public function showAction($id = null)
    {
//        $this->admin->setTemplate('show', 'Admin/Messaging/PendingApprovalMessage/CRUD/show.html.twig');
        return parent::showAction($id);
    }

    public function listAction()
    {
//        $this->admin->setTemplate('list', 'Admin/Messaging/Person/CRUD/list.html.twig');

        return parent::listAction();
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

        /** @var Message $object */
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
//        $template = $this->getTemplateRegistry()->getTemplate('decide');

        //		$template = $this->templateRegistry->getTemplate('show');

        $decision = $action;
        if ($decision === 'approve') {
            $object->setStatus(Message::STATUS_NEW);
            $this->admin->update($object);
        }


        if (!empty($res = $this->preRenderDecision($action, $object))) {
            return $res;
        }

        return $this->redirectToList();
    }
}
