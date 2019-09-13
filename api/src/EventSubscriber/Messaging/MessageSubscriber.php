<?php

namespace App\EventSubscriber\Messaging;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Messaging\Conversation;
use App\Entity\Messaging\FreeOnMessage;
use App\Entity\Organisation\IndividualMember;
use App\Entity\Messaging\Message;
use App\Entity\Messaging\OptionSet;
use App\Entity\Organisation\Organisation;
use App\Entity\Person\Person;
use App\Security\JWTUser;
use GuzzleHttp\Client;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class MessageSubscriber implements EventSubscriberInterface
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
        /** @var Message $message */
        $message = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        $manager = $this->registry->getEntityManager();

        if (!$message instanceof Message || Request::METHOD_POST !== $method) {
            return;
        }

        /** @var JWTUser $user */
        $user = $this->security->getUser();
        if (empty($user) or empty($imUuid = $user->getImUuid())) {
            $event->setResponse(new JsonResponse(['Unauthorised access! Empty user or Member'], 401));
        }

        $roles = $user->getRoles();
        if (!in_array('ROLE_ORG_ADMIN', $roles) && !in_array('ROLE_MSG_ADMIN', $roles) && $message->getPublished() === true) {
            if (!$message instanceof FreeOnMessage) {
                $message->setStatus(Message::STATUS_PENDING_APPROVAL);
            }
        }

        $imRepo = $this->registry->getRepository(IndividualMember::class);
        $im = $imRepo->findOneBy(['uuid' => $imUuid]);

//        $event->setResponse(new JsonResponse(['hello'=>'im','im'=>$im], 200));

        $message->setSender($im);
        $message->setOrganisation($im->getOrganisation());

//        $event->setControllerResult($connection);

//        throw new InvalidArgumentException('hello');

//        $event->setResponse(new JsonResponse(['attendee'=>$attendee->getRegistration()->getFamilyName(), 'user' => [
//            'im' => $user->getImUuid(),
//            'username' => $user->getUsername(), 'org' => $user->getOrgUuid()]], 200));

        if ($message instanceof FreeOnMessage) {
            $conversation = new Conversation();
            $conversation->addMessage($message);
            $conversation->addParticipant($message->getSender());
            $message->setStatus(Message::STATUS_NEW);

            if (empty($org)) {
                $org = $im->getOrganisation();
            }
            $msgAdmins = $org->getIndividualMembersWithMSGAdminRoleGranted();
            /** @var IndividualMember $participant */
            foreach ($msgAdmins as $participant) {
                $conversation->addParticipant($participant);
            }
        }
    }

    private function request($url, $token = null): array
    {
        $client = new Client([
            'verify' => false,
            'http_errors' => false,
            'curl' => [
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]
        ]);

        if ($token === null) $res = $client->request('GET', $url, []);
        else $res = $client->request('GET', $url, ['headers' => ['Authorization' => $token]]);

        if ($res->getStatusCode() !== 200) {
            throw new  \Exception('Request: ('.$url.') error: ('.$res->getStatusCode().')');
        }

        $result = json_decode($res->getBody()->getContents(), true);
        if (!is_array($result)) {
            throw new \Exception('Bad response: ('.$res->getBody()->getContents().')');
        }

        if (!isset($result['hydra:totalItems']) || $result['hydra:totalItems'] == 0) {
            throw new \Exception('Primary data ('.$url.') not found');
        }

        return $result['hydra:member'][0];
    }
}
