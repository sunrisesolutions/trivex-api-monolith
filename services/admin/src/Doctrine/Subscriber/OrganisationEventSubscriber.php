<?php

namespace App\Doctrine\Subscriber;

use App\Entity\Organisation\Organisation;
use App\Message\Message;
use App\Util\Organisation\AwsSnsUtil;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class OrganisationEventSubscriber implements EventSubscriber {

    private $awsSnsUtil;

    function __construct(AwsSnsUtil $awsSnsUtil)
    {
        $this->awsSnsUtil = $awsSnsUtil;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(){
        return [
            'postPersist',
            'postUpdate',
            'postRemove'
        ];
    }

    public function postPersist(LifecycleEventArgs $args) {
        $object = $args->getObject();
        if (!$object instanceof Organisation) {
            return;
        }
        return $this->awsSnsUtil->publishMessage($object, Message::OPERATION_POST);
    }

    public function postUpdate(LifecycleEventArgs $args) {
        $object = $args->getObject();
        if (!$object instanceof Organisation) {
            return;
        }
        return $this->awsSnsUtil->publishMessage($object, Message::OPERATION_PUT);
    }

    public function postRemove(LifecycleEventArgs $args) {
        $object = $args->getObject();
        if (!$object instanceof Organisation) {
            return;
        }
        $obj = new Organisation();
        $obj->setUuid($object->getUuid());
        return $this->awsSnsUtil->publishMessage($obj, Message::OPERATION_DELETE);
    }
}