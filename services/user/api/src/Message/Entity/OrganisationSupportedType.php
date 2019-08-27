<?php

namespace App\Message\Entity;

use App\Entity\Organisation;
use App\Entity\OrganisationUser;

class OrganisationSupportedType
{
    const individualMember = OrganisationUser::class;
    const organisation = Organisation::class;
}