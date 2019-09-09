<?php

namespace App\Message\Entity\User;

use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Organisation;

class OrganisationSupportedType
{
    const individualMember = IndividualMember::class;
    const organisation = Organisation::class;
}
