<?php

namespace App\Doctrine\Subscriber;

use App\Entity\Person\Person;
use App\Message\Message;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Util\Person\AwsSnsUtil;

class PersonEventSubsriber implements EventSubscriber {

    private $awsSnsUtil;

    public function __construct(AwsSnsUtil $awsSnsUtil) {
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
        if (!$object instanceof Person) {
            return;
        }
        return $this->awsSnsUtil->publishMessage($object, Message::OPERATION_POST);
    }

    public function postUpdate(LifecycleEventArgs $args) {
        $object = $args->getObject();
        if (!$object instanceof Person) {
            return;
        }
        return $this->awsSnsUtil->publishMessage($object, Message::OPERATION_PUT);
    }

    public function postRemove(LifecycleEventArgs $args) {
        $object = $args->getObject();
        if (!$object instanceof Person) {
            return;
        }
        return $this->awsSnsUtil->publishMessage($object, Message::OPERATION_DELETE);
    }
}