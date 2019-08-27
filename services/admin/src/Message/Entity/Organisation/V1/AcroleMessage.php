<?php

namespace App\Message\Entity\Organisation\V1;

use App\Entity\Organisation\Organisation;
use App\Message\Entity\Organisation\AcroleSupportedType;
use App\Message\Message;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;

class AcroleMessage extends Message
{
    protected function getSupportedType(): string
    {
        return AcroleSupportedType::class;
    }

    protected function prePersist($obj, $entity)
    {
        parent::prePersist($obj, $entity);
        $entity->organisationUuid = $obj->organisationUuid;
    }

    protected function getEntity(EntityManagerInterface $manager, ObjectRepository $repo, $obj)
    {
        $orgRepo = $manager->getRepository(Organisation::class);
        $org = $orgRepo->findOneBy(['uuid' => $obj->organisationUuid,
        ]);
        if (empty($org)) {
            return null;
        }
        return $entity = $repo->findOneBy(['name' => $obj->name, 'organisation' => $org]);
    }

}