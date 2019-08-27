<?php

namespace App\Admin;

use App\Entity\Organisation\Organisation;
use App\Service\Organisation\OrganisationService;
use App\Service\User\UserService;
use Sonata\AdminBundle\Admin\AbstractAdmin;

class BaseAdmin extends AbstractAdmin
{
    const AUTO_CONFIG = true;
    const ENTITY = null;
    const CONTROLLER = null;
    const CHILDREN = null;
    const ADMIN_CODE = null;
    const TEMPLATES = null;

    protected $translationDomain = 'AdminBundle'; // default is 'messages'

    /** @var UserService $userService */
    protected $userService;

    /** @var OrganisationService $organisationService */
    protected $organisationService;

    public function __construct($code, $class, $baseControllerName, UserService $userService, OrganisationService $organisationService)
    {
        parent::__construct($code, $class, $baseControllerName);
        $this->userService = $userService;
        $this->organisationService = $organisationService;
    }

    use BaseAdminTrait;
}
