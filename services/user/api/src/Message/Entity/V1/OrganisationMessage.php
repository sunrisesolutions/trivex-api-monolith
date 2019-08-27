<?php

namespace App\Message\Entity\V1;

use App\Entity\Organisation;
use App\Entity\OrganisationUser;
use App\Message\Entity\OrganisationSupportedType;
use App\Message\Message;
use App\Util\AppUtil;
use Doctrine\ORM\EntityManagerInterface;

class OrganisationMessage extends Message
{
    protected function getSupportedType(): string
    {
        return OrganisationSupportedType::class;
    }

    protected function prePersist($obj, $entity)
    {
        if (!$entity instanceof OrganisationUser) return;
        if (!empty($obj->roleString)) {
            $roles = json_decode($obj->roleString);
            $entity->setRoles($roles);
        }
    }
}