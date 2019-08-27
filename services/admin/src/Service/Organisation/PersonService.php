<?php

namespace App\Service\Organisation;

use App\Admin\BaseAdmin;
use App\Admin\Organisation\OrganisationAdmin;
use App\Entity\Person\Person;
use App\Entity\User\OrganisationUser;
use App\Entity\User\User;
use App\Service\ServiceContext;
use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Organisation;
use App\Service\BaseService;
use App\Service\User\UserService;
use App\Util\Organisation\AppUtil;
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

    public function updateEntity(\App\Entity\Organisation\Person $person)
    {
        $manager = $this->manager;

        $email = $person->getEmail();
        $phone = $person->getPhoneNumber();

        $pRepo = $manager->getRepository(\App\Entity\Person\Person::class);
        /** @var \App\Entity\Person\Person $fPerson */
        $fPerson = $pRepo->findOneBy(['email' => $email,
        ]);
        if (empty($fPerson)) {
            $fPerson = $pRepo->findOneBy(['phoneNumber' => $phone,
            ]);
        }
        if (empty($fPerson)) {
            $fPerson = new \App\Entity\Person\Person();
            AppUtil::copyObjectScalarProperties($person, $fPerson);
            $manager->persist($fPerson);
            $manager->flush();
        }

        AppUtil::copyObjectScalarProperties($fPerson, $person);

        if (method_exists($person, 'getNationality')) {
            $fNationality = $fPerson->getNationality();
            if (empty($person->getNationality())) {
                $person->createNationality();
            };
            $nationality = $person->getNationality();
            if (!empty($fNationality)) {
                $nationality->setUuid($fNationality->getUuid());
            } else {
                $fPerson->createNationality($nationality->getCountry(), $nationality->getNricNumber(), $nationality->getPassportNumber());
                $manager->persist($fPerson);
                $manager->flush($fPerson);
                $nationality->setUuid($fNationality->getUuid());
                $manager->persist($nationality);
            }
        }

        $this->copyPersonToUserModule($person, $fPerson);
    }

    private function copyPersonToUserModule(\App\Entity\Organisation\Person $person, Person $fPerson)
    {
        $manager = $this->manager;
        $upRepo = $manager->getRepository(\App\Entity\User\Person::class);

        $email = $person->getEmail();
        $phone = $person->getPhoneNumber();

        /** @var \App\Entity\User\Person $fuPerson */
        $fuPerson = $upRepo->findOneBy(['email' => $email,
        ]);
        if (empty($fuPerson)) {
            $fuPerson = $upRepo->findOneBy(['phoneNumber' => $phone,
            ]);
        }
        if (empty($fuPerson)) {
            $fuPerson = new \App\Entity\User\Person();
            AppUtil::copyObjectScalarProperties($person, $fuPerson);
            $manager->persist($fuPerson);
            $manager->flush();
        }

        if (method_exists($fuPerson, 'getNationality')) {
            $fNationality = $fPerson->getNationality();
            if (empty($fuPerson->getNationality())) {
                $fuPerson->createNationality();
            };
            $nationality = $fuPerson->getNationality();
            if (!empty($fNationality)) {
                $nationality->setUuid($fNationality->getUuid());
            } else {
                $fPerson->createNationality($nationality->getCountry(), $nationality->getNricNumber(), $nationality->getPassportNumber());
                $manager->persist($fPerson);
                $manager->flush($fPerson);
                $nationality->setUuid($fNationality->getUuid());
                $manager->persist($nationality);
            }
        }

        if (method_exists($person, 'getPassword')) {
            if (!empty($plainPassword = $person->getPassword()) && !empty($person->getEmail())) {
                if (empty($user = $fuPerson->getUser())) {
                    $fuPerson = $this->manager->getRepository(\App\Entity\User\Person::class)->findOneBy(['uuid' => $person->getUuid()]);
                    $user = $fuPerson->getUser();
                    if (empty($user)) {
                        $user = new  User();
                        $user->setEmail($email);
                        $user->setUsername($email);
                        $fuPerson->setUser($user);
                    }
                    $user->setPlainPassword($plainPassword);
                    $manager->persist($user);
                    $manager->flush();

                    $fPerson->setUserUuid($user->getUuid());
                    $manager->persist($fPerson);
                    $manager->flush();
                };
            }
        }
        if (method_exists($person, 'setPassword')) {
            $person->setPassword(null);
        }
    }

}
