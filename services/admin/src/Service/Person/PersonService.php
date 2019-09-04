<?php

namespace App\Service\Person;

use App\Admin\BaseAdmin;
use App\Admin\Organisation\OrganisationAdmin;
use App\Entity\Person\Person;
use App\Entity\User\User;
use App\Service\ServiceContext;
use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Organisation;
use App\Service\BaseService;
use App\Service\User\UserService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class PersonService extends BaseService
{
    private $userService;
    private $manager;

    public function __construct(ContainerInterface $container, UserService $userService)
    {
        parent::__construct($container);
        $this->userService = $userService;
        $this->manager = $container->get('doctrine.orm.default_entity_manager');
    }

    public function updateEntity(Person $person)
    {
        $manager = $this->manager;

        $email = $person->getEmail();
        $phone = $person->getPhoneNumber();

    }


}
