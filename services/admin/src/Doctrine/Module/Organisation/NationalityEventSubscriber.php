<?php

namespace App\Doctrine\Module\Organisation;

use App\Doctrine\Module\ORMEventSubscriber;
use App\Entity\Organisation\Nationality;
use App\Entity\Organisation\Organisation;
use App\Entity\Organisation\Person;
use App\Entity\Organisation\Role;
use App\Entity\User\User;
use App\Message\Message;
use App\Util\Organisation\AppUtil;
use App\Util\Organisation\AwsSnsUtil;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;

class NationalityEventSubscriber implements ORMEventSubscriber
{

    private $awsSnsUtil;
    private $manager;

    function __construct(AwsSnsUtil $awsSnsUtil, EntityManagerInterface $manager)
    {
        $this->awsSnsUtil = $awsSnsUtil;
        $this->manager = $manager;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
            Events::postLoad
        ];
    }

    private function updateEntity(Nationality $object)
    {
        $person = $object->getPerson();
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if (!$object instanceof Nationality) return;
        $this->updateEntity($object);
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if (!$object instanceof Nationality) return;
        $this->updateEntity($object);
    }


    public function postPersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if (!$object instanceof Nationality) return;
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if (!$object instanceof Nationality) return;
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if (!$object instanceof Nationality) {
            return;
        }
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        /** @var Nationality $object */
        $object = $args->getObject();
        if (!$object instanceof Nationality) {
            return;
        }
        if (!empty($person = $object->getPerson())) {
//            if (empty($person->getName())) {
            $person->combineData();
//            }
        }

        return;
    }
}