<?php

namespace App\Doctrine\Subscriber;

use App\Doctrine\Module\ORMEventSubscriber;
use App\Entity\Person\Person;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PersonEventSubsriber implements ORMEventSubscriber {

    public function __construct() {
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(){
        return [
            'prePersist',
            'postPersist',
            'postUpdate',
            'postRemove'
        ];
    }

    public function prePersist(LifecycleEventArgs $args) {
        $object = $args->getObject();
        if (!$object instanceof Person) {
            return;
        }
    }

    public function postPersist(LifecycleEventArgs $args) {
        $object = $args->getObject();
        if (!$object instanceof Person) {
            return;
        }
    }

    public function postUpdate(LifecycleEventArgs $args) {
        $object = $args->getObject();
        if (!$object instanceof Person) {
            return;
        }
    }

    public function postRemove(LifecycleEventArgs $args) {
        $object = $args->getObject();
        if (!$object instanceof Person) {
            return;
        }
    }
}
