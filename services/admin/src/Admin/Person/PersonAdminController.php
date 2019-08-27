<?php

namespace App\Admin\Person;

use App\Admin\BaseCRUDAdminController;
use App\Service\Organisation\OrganisationService;
use App\Entity\Person\Person;
use App\Entity\Classification\Category;
use App\Entity\Classification\CategoryItem\PersonCategoryItem;
use App\Service\ServiceContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PersonAdminController extends BaseCRUDAdminController
{

    /** @var PersonAdmin $admin */
    protected $admin;

    public function renderWithExtraParams($view, array $parameters = [], Response $response = null)
    {
        if ($parameters['action'] === 'show') {
            /** @var Person $person */
            $person = $this->admin->getSubject();
            $orgSlug = "1";
            $accessCode = "1";
            $employeeCode = "1";
            $parameters['base_person_template'] = 'standard_layout.html.twig';
            $parameters['person'] = $person;
            $parameters['mainContentItem'] = $person;
            $parameters['subContentItems'] = $person->getRootChapters();
            $parameters['orgSlug'] = $orgSlug;
            $parameters['accessCode'] = $accessCode;
            $parameters['employeeCode'] = $employeeCode;
        }

        return parent::renderWithExtraParams($view, $parameters, $response);
    }

    public function publishAction(Request $request, $id = null)
    {
        $request = $this->getRequest();
        $id = $request->get($this->admin->getIdParameter());

        /** @var Person $object */
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s', $id));
        }

        if ($request->isMethod('get')) {
            return new RedirectResponse($this->get('router')->generate('admin_magenta_cpersonmodel_person_person_show', ['id' => $object->getId()]));
        }

        if ($object->getStatus() !== Person::STATUS_DRAFT) {
            $this->addFlash('error', 'Not a Draft Version so it cannot be published!');
            return new RedirectResponse($this->get('router')->generate('admin_magenta_cpersonmodel_person_person_show', ['id' => $object->getId()]));
        }

        $edition = $request->request->get('edition-text');
        $object->setPersonEdition($edition);

        $clonedPerson = $object->publish();
        $manager = $this->get('doctrine.orm.default_entity_manager');
        $manager->persist($object);
        $manager->persist($clonedPerson);
        $manager->flush();

        $this->addFlash('success', 'Person Edition ' . $object->getPersonEdition() . ' has been published');
        return new RedirectResponse($this->get('router')->generate('admin_magenta_cpersonmodel_person_person_show', ['id' => $clonedPerson->getId()]));
    }

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
        $this->admin->setTemplate('show', 'Admin/Person/Person/CRUD/show.html.twig');
        return parent::showAction($id);
    }

    public function listAction()
    {
        $this->admin->setTemplate('list', 'Admin/Person/Person/CRUD/list.html.twig');

        return parent::listAction();
    }
}
