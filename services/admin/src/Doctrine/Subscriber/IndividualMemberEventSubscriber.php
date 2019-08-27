<?php

namespace App\Doctrine\Subscriber;

use App\Entity\Organisation\Event;
use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Organisation;
use App\Entity\Organisation\Person;
use App\Entity\Organisation\Role;
use App\Message\Message;
use App\Util\Organisation\AppUtil;
use App\Util\Organisation\AwsSnsUtil;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Events;

class IndividualMemberEventSubscriber implements EventSubscriber
{

    private $awsSnsUtil;

    function __construct(AwsSnsUtil $awsSnsUtil)
    {
        $this->awsSnsUtil = $awsSnsUtil;
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

        $names = [];
        foreach ($object->getRoles() as $role) $names[] = $role->getName();
        $ar['data']['individualMember']['roleString'] = json_encode($names);

        return $this->awsSnsUtil->publishMessage(json_encode($ar), Message::OPERATION_POST);
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

        $names = [];
        foreach ($object->getRoles() as $role) $names[] = $role->getName();
        $ar['data']['individualMember']['roleString'] = json_encode($names);

        return $this->awsSnsUtil->publishMessage(json_encode($ar), Message::OPERATION_PUT);
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if (!$object instanceof IndividualMember) {
            return;
        }
        $obj = new IndividualMember();
        $obj->setUuid($object->getUuid());
        return $this->awsSnsUtil->publishMessage($obj, Message::OPERATION_DELETE);
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
