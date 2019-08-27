<?php

namespace App\Message\Entity\User\V1;

use App\Entity\User\Organisation;
use App\Entity\User\OrganisationUser;
use App\Message\Entity\User\OrganisationSupportedType;
use App\Message\Message;
use App\Util\User\AppUtil;
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