<?php

namespace App\Service;

use App\Entity\Delivery;
use App\Entity\IndividualMember;
use App\Entity\Message;
use App\Entity\NotifSubscription;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IndividualMemberService
{
    private $manager;
    private $container;

    public function __construct(EntityManagerInterface $manager, ContainerInterface $container)
    {
        $this->manager = $manager;
        $this->container = $container;
    }

    public function notifyOneOrganisationIndividualMembers(Message $message)
    {
        $row = 0;
        $response = [];

        try {
            $memberRepo = $this->manager->getRepository(IndividualMember::class);
            ////////////// PWA Púh ////////////
//            $members = $memberRepo->findHavingOrganisationSubscriptions((int) $dp->getOwnerId());
            $webPushObjs = [];

            $path = $this->container->getParameter('PWA_PUBLIC_KEY_PATH');
            $pwaPublicKey = trim(file_get_contents($path));
            $path = $this->container->getParameter('PWA_PRIVATE_KEY_PATH');
            $pwaPrivateKey = trim(file_get_contents($path));

            $auth = [
                'VAPID' => [
                    'subject' => 'mailto:peter@magenta-wellness.com',
                    'publicKey' => $pwaPublicKey,
                    'privateKey' => $pwaPrivateKey, // in the real world, this would be in a secret file
                ],
            ];

            $webPush = new WebPush($auth);
            $webPush->setReuseVAPIDHeaders(true);

            while (!empty($deliveries = $message->commitDeliveries())) {
                ++$row;
                $rowNotif = 0;
                /** @var Delivery $delivery */
                foreach ($deliveries as $delivery) {
                    $this->manager->persist($delivery);
                    $this->manager->flush($delivery);
//                }
                    $member = $delivery->getRecipient();

//                $multipleRun = false;
                    /*
                     * @var IndividualMember
                     */
//            while (!empty($members = $message->getRecipientsByPage())) {
//                /** @var IndividualMember $member */
//                foreach ($members as $member) {
//                    if ($member->getUuid() === $message->getSender()->getUuid()) {
//                        continue;
//                    }
//                    if ($row > 1000) {
//                        $multipleRun = true;
//                        break;
//                    }

                    $subscriptions = $member->getNotifSubscriptions();

                    $preparedSubscriptions = [];
                    /**
                     * @var NotifSubscription $_sub
                     */
                    foreach ($subscriptions as $_sub) {
                        $rowNotif++;
                        $preparedSub = Subscription::create(
                            [
                                'endpoint' => $_sub->getEndpoint(),
                                'publicKey' => $_sub->getP256dhKey(),
                                'authToken' => $_sub->getAuthToken(),
                                'contentEncoding' => $_sub->getContentEncoding(), // one of PushManager.supportedContentEncodings
                            ]
                        );
                        $preparedSubscriptions[] = $preparedSub;

                        $notificationPayload = [
                            'notification' => [
                                'title' => $message->getSubject(),
                                'body' => $message->getBody(),
                                'icon' => 'assets/img/brand/T-Logo.png',
                                'vibrate' => [100, 50, 100],
                                'data' => [
                                    'messageId' => $message->getId(),
                                    'messageUuid' => $message->getUuid(),
                                    'deliveryId' => $delivery->getId(),
                                    'deliveryUuid' => $delivery->getUuid()
                                ],
                                'actions' => [
                                    [
                                        'action' => 'explore',
                                        'title' => 'View message'
                                    ]
                                ]
                            ]
                        ];

                        $webPush->sendNotification(
                            $preparedSub,
                            json_encode($notificationPayload),
                            false
                        );
                    }

//                    $recipient = $member;
//                    $delivery = MessageDelivery::createInstance($message, $recipient);
                }

                if ($rowNotif > 0) {
                    $response[] = 'pushing '.$rowNotif.' notifs';
                } else {
                    $response[] = 'No push notif for '.$member->getId().' '.$member->getPerson()->getName();
                }

                $pushReport = [];
                /** @var \Minishlink\WebPush\MessageSentReport $report */
                foreach ($webPush->flush($rowNotif) as $report) {
                    $endpoint = $report->getEndpoint();
                    if ($report->isSuccess()) {
                        $pushReport[] = $response[] = "[v] Message sent successfully for subscription {$endpoint}.";
                    } else {
                        $pushReport[] = $response[] = "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";

                        // also available (to get more info)

                        /** @var \Psr\Http\Message\RequestInterface $requestToPushService */
                        $requestToPushService = $report->getRequest();

                        /** @var \Psr\Http\Message\ResponseInterface $responseOfPushService */
                        $responseOfPushService = $report->getResponse();

                        /** @var string $failReason */
                        $failReason = $report->getReason();

                        /** @var bool $isTheEndpointWrongOrExpired */
                        $isTheEndpointWrongOrExpired = $report->isSubscriptionExpired();
                    }
                }
                if (count($pushReport) === 0) {
                    $response[] = 'no notifs were flushed';
                    $response[] = $webPush->countPendingNotifications().' pending notifs';
                    $response[] = 'automatic padding is '.$webPush->getAutomaticPadding();

                    /** @var \Minishlink\WebPush\MessageSentReport $report */
                    foreach ($webPush->flush() as $report) {
                        $endpoint = $report->getEndpoint();
                        if ($report->isSuccess()) {
                            $pushReport[] = $response[] = "[v] Message sent successfully for subscription {$endpoint}.";
                        } else {
                            $pushReport[] = $response[] = "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";

                            // also available (to get more info)

                            /** @var \Psr\Http\Message\RequestInterface $requestToPushService */
                            $requestToPushService = $report->getRequest();

                            /** @var \Psr\Http\Message\ResponseInterface $responseOfPushService */
                            $responseOfPushService = $report->getResponse();

                            /** @var string $failReason */
                            $failReason = $report->getReason();

                            /** @var bool $isTheEndpointWrongOrExpired */
                            $isTheEndpointWrongOrExpired = $report->isSubscriptionExpired();
                        }
                    }
                    if (count($pushReport) === 0) {
                        $response[] = '(2nd try): no notifs were flushed';
                        $response[] = $webPush->countPendingNotifications().' pending notifs';
                    }
                }
            }

            $response[] = "let's push again after while loop";
            $pushReport = [];
            $webPush->flush();
            /** @var \Minishlink\WebPush\MessageSentReport $report */
            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getEndpoint();
                if ($report->isSuccess()) {
                    $pushReport[] = $response[] = "[v] Message sent successfully for subscription {$endpoint}.";
                } else {
                    $pushReport[] = $response[] = "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";

                    // also available (to get more info)

                    /** @var \Psr\Http\Message\RequestInterface $requestToPushService */
                    $requestToPushService = $report->getRequest();

                    /** @var \Psr\Http\Message\ResponseInterface $responseOfPushService */
                    $responseOfPushService = $report->getResponse();

                    /** @var string $failReason */
                    $failReason = $report->getReason();

                    /** @var bool $isTheEndpointWrongOrExpired */
                    $isTheEndpointWrongOrExpired = $report->isSubscriptionExpired();
                    if ($isTheEndpointWrongOrExpired) {
                        $notif = $this->manager->getRepository(NotifSubscription::class)->findOneBy(['endpoint' => $endpoint]);
                        if (!empty($notif)) {
                            $this->manager->remove($notif);
                        }
                    }
                }
            }
            if (count($pushReport) === 0) {
                $response[] = 'no notifs were flushed';
                $response[] = $webPush->countPendingNotifications().' pending notifs';
                $response[] = 'automatic padding is '.$webPush->getAutomaticPadding();

                /** @var \Minishlink\WebPush\MessageSentReport $report */
                foreach ($webPush->flush() as $report) {
                    $endpoint = $report->getEndpoint();
                    if ($report->isSuccess()) {
                        $pushReport[] = $response[] = "[v] Message sent successfully for subscription {$endpoint}.";
                    } else {
                        $pushReport[] = $response[] = "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";

                        // also available (to get more info)

                        /** @var \Psr\Http\Message\RequestInterface $requestToPushService */
                        $requestToPushService = $report->getRequest();

                        /** @var \Psr\Http\Message\ResponseInterface $responseOfPushService */
                        $responseOfPushService = $report->getResponse();

                        /** @var string $failReason */
                        $failReason = $report->getReason();

                        /** @var bool $isTheEndpointWrongOrExpired */
                        $isTheEndpointWrongOrExpired = $report->isSubscriptionExpired();
                    }
                }
                if (count($pushReport) === 0) {
                    $response[] = '(2nd try): no notifs were flushed';
                    $response[] = $webPush->countPendingNotifications().' pending notifs';
                }
            }


            if (!$this->manager->isOpen()) {
                throw new \Exception('EM is closed before flushed '.$row);
            } else {

            }
            $message->setStatus(Message::STATUS_DELIVERY_SUCCESSFUL);
            $this->manager->persist($message);
            $this->manager->flush();
        } catch (OptimisticLockException $ope) {
            throw $ope;
        } catch (ORMException $orme) {
            throw $orme;
        } catch (\Exception $e) {
            throw $e;
        }
        return $response;
    }
}