<?php

namespace App\Doctrine\Subscriber;

use App\Entity\Organisation\Organisation;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class OrganisationEventSubscriber implements EventSubscriber {

    function __construct()
    {
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
    }

    public function postUpdate(LifecycleEventArgs $args) {
        $object = $args->getObject();
        if (!$object instanceof Organisation) {
            return;
        }
    }

    public function postRemove(LifecycleEventArgs $args) {
        $object = $args->getObject();
        if (!$object instanceof Organisation) {
            return;
        }
    }
}
