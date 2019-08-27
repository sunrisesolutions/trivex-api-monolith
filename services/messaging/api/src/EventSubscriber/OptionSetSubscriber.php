<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Delivery;
use App\Entity\IndividualMember;
use App\Entity\Message;
use App\Entity\OptionSet;
use App\Entity\Organisation;
use App\Security\JWTUser;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class OptionSetSubscriber implements EventSubscriberInterface
{
    private $registry;
    private $mailer;
    private $security;

    public function __construct(RegistryInterface $registry, \Swift_Mailer $mailer, Security $security)
    {
        $this->registry = $registry;
        $this->mailer = $mailer;
        $this->security = $security;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['onKernelView', EventPriorities::PRE_WRITE],
        ];
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        /** @var OptionSet $ops */
        $ops = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $manager = $this->registry->getEntityManager();

        if (!$ops instanceof OptionSet || Request::METHOD_POST !== $method) {
            return;
        }

        $org = $this->registry->getRepository(Organisation::class)->findOneBy(['uuid' => $ops->getOrganisationUuid()]);
        if (!empty($org)) {
            $ops->setOrganisation($org);
            $org->addOptionSet($ops);
            $manager->persist($org);
        }
    }
}
