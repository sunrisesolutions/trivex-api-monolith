<?php

namespace App\EventSubscriber\Event;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Event\Attendee;
use App\Entity\Organisation\IndividualMember;
use App\Security\JWTUser;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class AttendeeSubscriber implements EventSubscriberInterface
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

    public function onKernelView(ViewEvent $event)
    {
        /** @var Attendee $attendee */
        $attendee = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$attendee instanceof Attendee || Request::METHOD_POST !== $method) {
            return;
        }

        /** @var JWTUser $user */
        $user = $this->security->getUser();
        if (empty($user)) {
            return;
        }

        $imUuid = $user->getImUuid();
        $reg = $attendee->getRegistration();
        $reg->setMemberUuid($imUuid);
        /** @var IndividualMember $im */
        $im = $this->registry->getRepository(IndividualMember::class)->findOneBy(['uuid' => $imUuid,
        ]);

        if (!empty($im)) {
            $im->addRegistration($reg);
        }

//        throw new InvalidArgumentException('hello');

//        $event->setResponse(new JsonResponse(['attendee'=>$attendee->getRegistration()->getFamilyName(), 'user' => [
//            'im' => $user->getImUuid(),
//            'username' => $user->getUsername(), 'org' => $user->getOrgUuid()]], 200));
    }
}
