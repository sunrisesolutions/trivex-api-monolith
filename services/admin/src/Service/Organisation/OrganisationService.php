<?php

namespace App\Service\Organisation;

use App\Admin\BaseAdmin;
use App\Admin\Organisation\OrganisationAdmin;
use App\Entity\User\OrganisationUser;
use App\Service\ServiceContext;
use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Organisation;
use App\Service\BaseService;
use App\Service\User\UserService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OrganisationService extends BaseService
{
    private $userService;

    public function __construct(ContainerInterface $container, UserService $userService)
    {
        parent::__construct($container);
        $this->userService = $userService;
    }

    public function getCurrentOrganisation(ServiceContext $context, $required = true, $orgClass = Organisation::class)
    {
        $registry = $this->container->get('doctrine');
        if (!empty($orgUuid = $this->getRequest()->query->getInt('organisation', 0))) {
            $org = $registry->getRepository(Organisation::class)->findOneBy(['uuid' => $orgUuid]);
        } else {
            if ($context->getType() === ServiceContext::TYPE_ADMIN_CLASS) {
                $org = $this->getCurrentOrganisationFromAncestors($context->getAttribute('parent'));
            }
        }

        if (empty($org)) {
            $user = $this->userService->getUser();

            if (empty($org = $user->getAdminOrganisations()->first())) {
                /** @var OrganisationUser $ou */
                $ou = $user->getOrganisationUsers()->first();
                if (!empty($ou)) {
                    $org = $ou->getOrganisation();
                }
            }
        }

        if (empty($org)) {
            $org = null;
            if ($required) {
                throw new UnauthorizedHttpException('Unauthorised access');
            }
        }

        if (!empty($org) && get_class($org) !== $orgClass) {
            $org = $registry->getRepository($orgClass)->findOneBy(['uuid' => $org->getUuid()]);
        }

        return $org;
    }

    public function getCurrentOrganisationFromAncestors(BaseAdmin $parent = null)
    {
        if (empty($parent)) {
            return null;
        }
        if ($parent instanceof OrganisationAdmin) {
            return $parent->getSubject();
        }
        $grandpa = $parent->getParent();
        if ($grandpa instanceof OrganisationAdmin) {
            return $grandpa->getSubject();
        } else {
            return $this->getCurrentOrganisationFromAncestors($grandpa);
        }

    }
}
