<?php

namespace App\Message\Entity\User;

use App\Entity\User\Organisation;
use App\Entity\User\OrganisationUser;

class OrganisationSupportedType
{
    const individualMember = OrganisationUser::class;
    const organisation = Organisation::class;
}