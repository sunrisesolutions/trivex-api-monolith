<?php

namespace App\EventSubscriber\Organisation;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Event\Attendee;
use App\Entity\Organisation\Connection;
use App\Entity\Organisation\IndividualMember;
use App\Repository\Organisation\ConnectionRepository;
use App\Security\JWTUser;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class ConnectionSubscriber implements EventSubscriberInterface
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
        /** @var Connection $connection */
        $connection = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$connection instanceof Connection || Request::METHOD_POST !== $method) {
            return;
        }

        /** @var JWTUser $user */
        $user = $this->security->getUser();
        if (empty($user) or empty($imUuid = $user->getImUuid())) {
            $event->setResponse(new JsonResponse(['Unauthorised access! Empty user or Member'], 401));
        }

        $imRepo = $this->registry->getRepository(IndividualMember::class);
        $im = $imRepo->findOneBy(['uuid' => $imUuid,
        ]);
//        $event->setResponse(new JsonResponse(['hello'=>'im','im'=>$im], 200));

        $connection->setFromMember($im);

        /** @var ConnectionRepository $connectionRepo */
        $connectionRepo = $this->registry->getRepository(Connection::class);
        $myMemberUuid = $connection->getFromMember()->getUuid();
        $friendMemberUuid = $connection->getToMember()->getUuid();
        $existingConnections = $connectionRepo->findByFriendMemberUuid($myMemberUuid, $friendMemberUuid);
        if (!empty($existingConnections)) {
            /** @var Connection $connection */
            $connection = $existingConnections[0];
            $connection->setUpdatedAt(new \DateTime());
            $event->setControllerResult($connection);

            $event->setResponse(new JsonResponse(['message' => 'Connections existing'], 200));
        }

//        $event->setControllerResult($connection);

//        throw new InvalidArgumentException('hello');

//        $event->setResponse(new JsonResponse(['attendee'=>$attendee->getRegistration()->getFamilyName(), 'user' => [
//            'im' => $user->getImUuid(),
//            'username' => $user->getUsername(), 'org' => $user->getOrgUuid()]], 200));
    }
}
