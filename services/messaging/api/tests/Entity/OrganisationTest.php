<?php

namespace App\Tests\Entity;

use App\Entity\Organisation;
use PHPUnit\Framework\TestCase;

class OrganisationTest extends TestCase
{
    public function testGetIndividualMembersByPage()
    {
        $org = new Organisation();
        $ims = $org->getIndividualMembersByPage();
        $this->assertGreaterThan(0, $ims->count());
    }
}