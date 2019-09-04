<?php

namespace App\Doctrine\Subscriber;

use App\Entity\Organisation;
use App\Entity\Role;
use App\Message\Message;
use App\Util\AwsSnsUtil;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoleEventSubscriber implements EventSubscriber
{

    private $awsSnsUtil;

    function __construct(AwsSnsUtil $awsSnsUtil)
    {
        $this->awsSnsUtil = $awsSnsUtil;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        $em = $args->getObjectManager();

        if (!$object instanceof Role) {
            return;
        }

        if (isset($object->organisationUuid) && !empty($object->organisationUuid)) {
//            echo 'yes isset';
            $orgRepo = $em->getRepository(Organisation::class);
            $org = $orgRepo->findOneBy(['uuid' => $object->organisationUuid]);
            if (empty($org)) {
                throw new NotFoundHttpException('Org not found in RoleEventSubscriber');
            } else {
                $org->addRole($object);
                $em->persist($org);
            }

        } else {
//            echo 'no isset';
        }
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        $em = $args->getObjectManager();

        if (!$object instanceof Role) {
            return;
        }

        if (isset($object->organisationUuid) && !empty($object->organisationUuid)) {
//            echo 'yes isset';
            $orgRepo = $em->getRepository(Organisation::class);
            $org = $orgRepo->findOneBy(['uuid' => $object->organisationUuid]);
            if (empty($org)) {
                throw new NotFoundHttpException('Org not found in RoleEventSubscriber');
            } else {
                $org->addRole($object);
                $em->persist($org);
            }

        } else {
//            echo 'no isset';
        }
    }
}