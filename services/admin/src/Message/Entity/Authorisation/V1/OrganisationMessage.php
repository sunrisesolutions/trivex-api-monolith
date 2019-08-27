<?php

namespace App\Message\Entity\Authorisation\V1;

use App\Entity\Authorisation\Organisation;
use App\Message\Entity\Authorisation\OrganisationSupportedType;
use App\Message\Message;
use App\Util\Authorisation\AppUtil;
use Doctrine\ORM\EntityManagerInterface;

class OrganisationMessage extends Message
{
    protected function getSupportedType(): string {
        return OrganisationSupportedType::class;
    }

}