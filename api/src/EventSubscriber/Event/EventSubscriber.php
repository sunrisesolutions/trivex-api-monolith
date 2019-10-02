<?php

namespace App\EventSubscriber\Event;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Event\Event;
use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Organisation;
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

class EventSubscriber implements EventSubscriberInterface
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
        /** @var Event $appEvent */
        $appEvent = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$appEvent instanceof Event || Request::METHOD_POST !== $method) {
            return;
        }

        /** @var JWTUser $user */
        $user = $this->security->getUser();
        if (empty($user)) {
            return;
        }

        $orgUuid = $user->getOrgUuid();
        /** @var IndividualMember $im */
        $org = $this->registry->getRepository(Organisation::class)->findOneBy(['uuid' => $orgUuid,
        ]);

        $appEvent->setOrganisation($org);

//        throw new InvalidArgumentException('hello');

//        $event->setResponse(new JsonResponse(['attendee'=>$attendee->getRegistration()->getFamilyName(), 'user' => [
//            'im' => $user->getImUuid(),
//            'username' => $user->getUsername(), 'org' => $user->getOrgUuid()]], 200));
    }
}
