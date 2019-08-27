<?php

namespace App\Message\Entity\Organisation\V1;

use App\Entity\Organisation\Organisation;
use App\Message\Entity\Organisation\PersonSupportedType;
use App\Message\Message;
use App\Util\Organisation\AppUtil;
use Doctrine\ORM\EntityManagerInterface;

class PersonMessage extends Message
{
    protected function getSupportedType(): string{
        return PersonSupportedType::class;
    }

}