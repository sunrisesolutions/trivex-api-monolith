<?php

namespace App\Entity\Organisation;

use App\Util\Organisation\AppUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Organisation\RoleRepository")
 * @ORM\Table(name="organisation__role")
 * @ORM\HasLifecycleCallbacks()
 */
class Role
{
    const ROLE_MESSAGE_ADMIN = 'ROLE_MSG_ADMIN';
    const ROLE_ORGANISATION_ADMIN = 'ROLE_ORG_ADMIN';

    /**
     * @var int|null The Event Id
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid(AppUtil::APP_NAME.'_ROLE');
        }
    }

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    public function getNameTrans()
    {
        if ($this->name === 'ROLE_ORG_ADMIN') {
            return 'ADMIN';
        }
        if ($this->name === 'ROLE_MSG_ADMIN') {
            return 'MESSAGE ADMIN';
        }
        if ($this->name === 'ROLE_EVENT_ADMIN') {
            return 'EVENT ADMIN';
        }
        if ($this->name === 'ROLE_MSG_USER') {
            return 'MESSAGE USER';
        }
        return $this->name;
    }

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Organisation\Organisation", inversedBy="roles")
     * @ORM\JoinColumn(name="id_organisation", referencedColumnName="id", onDelete="CASCADE")
     */
    private $organisation;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Organisation\IndividualMember", mappedBy="roles")
     */
    private $individualMembers;

    public function __construct()
    {
        $this->individualMembers = new ArrayCollection();
    }

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getOrganisation(): ?Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(?Organisation $organisation): self
    {
        $this->organisation = $organisation;

        return $this;
    }

    /**
     * @return Collection|IndividualMember[]
     */
    public function getIndividualMembers(): Collection
    {
        return $this->individualMembers;
    }

    public function addIndividualMember(IndividualMember $individualMember): self
    {
        if (!$this->individualMembers->contains($individualMember)) {
            $this->individualMembers[] = $individualMember;
            $individualMember->addRole($this);
        }

        return $this;
    }

    public function removeIndividualMember(IndividualMember $individualMember): self
    {
        if ($this->individualMembers->contains($individualMember)) {
            $this->individualMembers->removeElement($individualMember);
            $individualMember->removeRole($this);
        }

        return $this;
    }
}
