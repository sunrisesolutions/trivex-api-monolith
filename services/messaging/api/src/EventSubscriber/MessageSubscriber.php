<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Conversation;
use App\Entity\FreeOnMessage;
use App\Entity\IndividualMember;
use App\Entity\Message;
use App\Entity\OptionSet;
use App\Entity\Organisation;
use App\Entity\Person;
use App\Security\JWTUser;
use GuzzleHttp\Client;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
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

    public function onKernelView(GetResponseForControllerResultEvent $event)
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
        if (empty($im)) {
            $token = $event->getRequest()->headers->get('Authorization');
            $imData = $this->request('https://'.getenv('ORG_SERVICE_HOST').'/individual_members?uuid='.$imUuid, $token);

            if (empty($personUuid = $imData['personData']['uuid']) || empty($orgUuid = $imData['organisationUuid'])) {
                throw new \Exception('personUuid | organisationUuid not found');
            }

            $person = $this->registry->getRepository(Person::class)->findOneBy(['uuid' => $personUuid]);
            $im = new IndividualMember();
            $im->setUuid($imData['uuid']);

            if (empty($person)) {
                $person = new Person();
                $person->setUuid($personUuid);
                $person->setName($imData['personData']['name']);
            }
            $person->addIndividualMember($im);
            $manager->persist($person);

            $org = $this->registry->getRepository(Organisation::class)->findOneBy(['uuid' => $orgUuid]);
            if (empty($org)) {
                $orgData = $this->request('https://'.getenv('ORG_SERVICE_HOST').'/organisations?uuid='.$orgUuid, $token);
                $org = new Organisation();
                $org->setUuid($orgData['uuid']);
                $org->setName($orgData['name']);
            }
            $org->addIndividualMember($im);
            $org->addMessage($message);
            $manager->persist($org);

            $im->setOrganisation($org);
            $im->setPerson($person);
            $manager->persist($im);
        }

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
