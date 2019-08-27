<?php

namespace App\Message\Entity\Person\V1;

use App\Entity\Person\Organisation;
use App\Message\Entity\Person\PersonSupportedType;
use App\Message\Message;
use App\Util\Person\AppUtil;
use Doctrine\ORM\EntityManagerInterface;

class PersonMessage extends Message
{
    protected function getSupportedType(): string{
        return PersonSupportedType::class;
    }

}