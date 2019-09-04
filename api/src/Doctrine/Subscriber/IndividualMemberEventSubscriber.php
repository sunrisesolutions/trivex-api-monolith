<?php

namespace App\Doctrine\Subscriber;

use App\Entity\Event;
use App\Entity\Organisation\IndividualMember;
use App\Entity\Person;
use App\Util\AppUtil;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Events;

class IndividualMemberEventSubscriber implements EventSubscriber
{

    function __construct()
    {
    }

    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
            Events::postLoad,
        ];
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if (!$object instanceof IndividualMember) return;

        $ar = [
            'data' => [
                'individualMember' => [
                    'uuid' => $object->getUuid(),
                    'accessToken' => $object->getAccessToken(),
                    'personUuid' => $object->getPersonUuid(),
                    'organisationUuid' => $object->getOrganisationUuid(),
                    '_SYSTEM_OPERATION' => Message::OPERATION_POST,
                ]
            ],
            'version' => AppUtil::MESSAGE_VERSION,
        ];
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if (!$object instanceof IndividualMember) return;

        $ar = [
            'data' => [
                'individualMember' => [
                    'uuid' => $object->getUuid(),
                    'accessToken' => $object->getAccessToken(),
                    'personUuid' => $object->getPersonUuid(),
                    'organisationUuid' => $object->getOrganisationUuid(),
                    '_SYSTEM_OPERATION' => Message::OPERATION_PUT,
                ]
            ],
            'version' => AppUtil::MESSAGE_VERSION,
        ];
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if (!$object instanceof IndividualMember) {
            return;
        }
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        /** @var IndividualMember $object */
        $object = $args->getObject();
        if (!$object instanceof IndividualMember) {
            return;
        }
        if (!empty($person = $object->getPerson())) {
            if (empty($person->getName())) {
                $person->combineData();
            }
        }
        return;
    }
}
