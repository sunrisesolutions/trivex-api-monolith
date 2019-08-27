<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;

final class JWTUser extends \Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser implements JWTUserInterface
{
    // Your own logic

    private $orgUuid;

    private $imUuid;

    private $uuid;

    public function __construct($username, array $roles, $org, $im, $uuid)
    {
        parent::__construct($username, $roles);
        $this->orgUuid = $org;
        $this->imUuid = $im;
        $this->uuid = $uuid;
    }

    public static function createFromPayload($username, array $payload)
    {
        return new self(
            $username,
            $payload['roles'], // Added by default
            $payload['org'],  // Custom
            $payload['im'],  // Custom
            $payload['uuid']
        );
    }

    /**
     * @return mixed
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return mixed
     */
    public function getOrgUuid()
    {
        return $this->orgUuid;
    }

    /**
     * @return mixed
     */
    public function getImUuid()
    {
        return $this->imUuid;
    }
}
