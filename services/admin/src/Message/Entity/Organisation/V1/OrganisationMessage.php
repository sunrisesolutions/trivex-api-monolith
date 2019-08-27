<?php

namespace App\Message\Entity\Organisation\V1;

use App\Entity\Organisation\Organisation;
use App\Message\Entity\Organisation\OrganisationSupportedType;
use App\Message\Message;
use App\Util\Organisation\AppUtil;
use Doctrine\ORM\EntityManagerInterface;

class OrganisationMessage extends Message
{
    protected function getSupportedType(): string {
        return OrganisationSupportedType::class;
    }

}