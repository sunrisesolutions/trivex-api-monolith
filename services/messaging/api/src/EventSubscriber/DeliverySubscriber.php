<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Delivery;
use App\Entity\IndividualMember;
use App\Entity\Message;
use App\Security\JWTUser;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class DeliverySubscriber implements EventSubscriberInterface
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
            KernelEvents::VIEW => ['onKernelView', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        /** @var Delivery $delivery */
        $delivery = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$delivery instanceof Delivery || Request::METHOD_PUT !== $method) {
            return;
        }

        if ($delivery->getRead() && empty($delivery->getReadAt())) {
            $delivery->setReadAt(new \DateTime());
        }

        if ($delivery->getReadSelectedOptions() && empty($delivery->getSelectedOptionsReadAt())) {
            $delivery->setSelectedOptionsReadAt(new \DateTime());
        }

        if (!empty($delivery->getSelectedOptions()) && empty($delivery->getOptionsSelectedAt())) {
            $delivery->setOptionsSelectedAt(new \DateTime());
        }

//        $event->setResponse(new JsonResponse(['hello'=>'im','im'=>$im], 200));


//        $event->setControllerResult($connection);

//        throw new InvalidArgumentException('hello');

//        $event->setResponse(new JsonResponse(['attendee'=>$attendee->getRegistration()->getFamilyName(), 'user' => [
//            'im' => $user->getImUuid(),
//            'username' => $user->getUsername(), 'org' => $user->getOrgUuid()]], 200));
    }
}
