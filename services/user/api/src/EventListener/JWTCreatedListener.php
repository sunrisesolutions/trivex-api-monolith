<?php

namespace App\EventListener;

use App\Entity\OrganisationUser;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTCreatedListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        /** @var User $user */
        $user = $event->getUser();
        $payload = $event->getData();

        if (!empty($request)) {
            $payload['ip'] = $request->getClientIp();


            $payload['org'] = $request->attributes->get('orgUid');
            $payload['im'] = $request->attributes->get('imUid');

            if (empty($payload['org'])) {
                $imUuid = $request->request->get('im-uuid');
                if (empty($imUuid)) {
                    /** @var OrganisationUser $im */
                    $im = $user->getOrganisationUsers()->first();
                    $imUuid = $im->getUuid();
                    $orgUuid = $im->getOrganisation()->getUuid();
                } else {
                    $im = $user->findOrgUserByUuid($imUuid);
                    $orgUuid = $im->getOrganisation()->getUuid();
                }
                $payload['org'] = $orgUuid;
                $payload['im'] = $imUuid;
            }
        } else {
            $payload['ip'] = null;
        }
//        $payload['uuid'] = $user->getUuid();

        $event->setData($payload);

//        $header = $event->getHeader();
//        $header['cty'] = 'JWT';
//
//        $event->setHeader($header);
    }
}
