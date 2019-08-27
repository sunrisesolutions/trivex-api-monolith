<?php

namespace App\Entity\Authorisation;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Util\Authorisation\AppUtil;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource(shortName="acentry")
 * @ORM\Entity(repositoryClass="App\Repository\Authorisation\ACEntryRepository")
 * @ORM\Table(name="authorisation__entry")
 * @ORM\HasLifecycleCallbacks()
 */
class ACEntry
{
//    const PERMISSION_CREATE = 'CREATE';
//    const PERMISSION_READ = 'READ';
//    const PERMISSION_UPDATE = 'UPDATE';
//    const PERMISSION_DELETE = 'DELETE';
//    const PERMISSION_LIST = 'LIST';
//    const PERMISSION_ASSIGN = 'ASSIGN';
//    const PERMISSION_RECEIVE = 'RECEIVE';
//    const PERMISSION_APPROVE = 'APPROVE';
//    const PERMISSION_REJECT = 'REJECT';

    const STATUS_GRANTED = 'GRANTED';
    const STATUS_DENIED = 'DENIED';
    const STATUS_EMPTY = 'EMPTY';

//    public static function getSupportedActions()
//    {
//        return [
//            self::PERMISSION_CREATE,
//            self::PERMISSION_READ,
//            self::PERMISSION_UPDATE,
//            self::PERMISSION_DELETE,
//        ];
//    }

    /**
     * @var int|null
     * @ORM\Id
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $uuid;

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid(AppUtil::APP_NAME.'_ENTRY');
        }
    }

    /**
     * @var ACRole
     * @ORM\ManyToOne(targetEntity="App\Entity\Authorisation\ACRole", inversedBy="entries", cascade={"persist","merge"})
     * @ORM\JoinColumn(name="id_role", referencedColumnName="id", onDelete="CASCADE")
     */
    private $role;

    /**
     * @ORM\Column(type="boolean", options={"default":true})
     */
    private $enabled = true;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $permission;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getRole(): ?ACRole
    {
        return $this->role;
    }

    public function setRole(?ACRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function setPermission(string $permission): self
    {
        $this->permission = $permission;

        return $this;
    }
}
