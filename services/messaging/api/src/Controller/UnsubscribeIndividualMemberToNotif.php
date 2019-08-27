<?php

namespace App\Controller;

use App\Entity\IndividualMember;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class UnsubscribeIndividualMemberToNotif
{
    private $mailer;
    private $registry;
    private $container;

    public function __construct(RegistryInterface $registry, \Swift_Mailer $mailer, ContainerInterface $container)
    {
        $this->mailer = $mailer;
        $this->registry = $registry;
        $this->container = $container;
    }

    public function __invoke(IndividualMember $data): IndividualMember
    {

//        /** @var IndividualMember $member */
//        $member = $this->registry->getRepository(IndividualMember::class)->find($data->emailTo);
        if (!empty($data)) {
            $manager = $this->container->get('doctrine.orm.default_entity_manager');
            $subs = $data->getNotifSubscriptions();
            foreach ($subs as $sub) {
                $manager->remove($sub);
            }
            $manager->flush();
        }
        return $data;
    }
}