<?php

namespace App\Entity\Messaging;

use App\Util\Messaging\AppUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Messaging\RoleRepository")
 * @ORM\Table(name="messaging__role")
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

    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid(AppUtil::APP_NAME.'_IM');
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

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Messaging\Organisation", inversedBy="roles")
     * @ORM\JoinColumn(name="id_organisation", referencedColumnName="id", onDelete="SET NULL")
     */
    private $organisation;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Messaging\IndividualMember", mappedBy="roles")
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
