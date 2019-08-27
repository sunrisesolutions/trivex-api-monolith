<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\NotifSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;

class BeanPlaygroundController extends AbstractController
{
    private $ctn;
    public function __construct(ContainerInterface $container)
    {
        $this->ctn = $container;
    }

    /**
     * @Route("/bean/playground", name="bean_playground")
     */
    public function index()
    {
        $nRepo = $this->get('doctrine')->getRepository(NotifSubscription::class);
        $_sub = $nRepo->find(262);


        $path = $this->ctn->getParameter('PWA_PUBLIC_KEY_PATH');
        $pwaPublicKey = trim(file_get_contents($path));
        $path = $this->ctn->getParameter('PWA_PRIVATE_KEY_PATH');
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

        $preparedSub = Subscription::create(
            [
                'endpoint' => "https://fcm.googleapis.com/fcm/send/dW92gIzFqSE:APA91bFj1QQ9jbFgegv9r5n6vwNoFaF5pzo9A_YflCcV1tt44kqD8K1sJNaYihgk4XCDHSOXb3WVGEBj9YyRPqMJoZ8P7_aI2ooSPXisgrtzWoAs5087suMyDegW21IUL9YqQ-eJ2591", //$_sub->getEndpoint(),
                'publicKey' => "BNdI-J7s7_Cv3PJOccbnL_LteoEZ5eeqnGcwBzjvQ3L4B1o7IrozmmUl3Fd0wOjlJNMLnFQGYPgyWdaspT5Z7GA",// $_sub->getP256dhKey(), //,
                'authToken' => "Sv1eXmOgAbBZX36jYSaphg", //$_sub->getAuthToken(), //,
//                'contentEncoding' => "aes128gcm" // $_sub->getContentEncoding(), // one of PushManager.supportedContentEncodings
            ]
        );
        $message = new Message();
        $message->setSubject('SUBJJJJJJJJJ');
        $message->setBody('BODYDYDYDYD');
        $preparedSubscriptions[] = $preparedSub;
        $notificationPayload = [
            'notification' => [
                'title' => $message->getSubject(),
                'body' => $message->getBody(),
                'icon' => '/assets/img/brand/T-Logo.png',
                'vibrate' => [100, 50, 100],
                'data' => [
                    'messageId' => $message->getId(),
                    'messageUuid' => $message->getUuid()
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

        $res = $webPush->flush(3000);

        /** @var \Minishlink\WebPush\MessageSentReport $report */
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();

            if ($report->isSuccess()) {
                echo "[v] Message sent successfully for subscription {$endpoint}.";
            } else {
                echo "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";

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


        return $this->render('bean_playground/index.html.twig', [
            'controller_name' => 'BeanPlaygroundController',
            'payload'=>json_encode($notificationPayload),
            'res' => $res
        ]);
    }
}
