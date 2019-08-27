<?php

namespace App\Entity\Authorisation;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Authorisation\OrganisationRepository")
 * @ORM\Table(name="authorisation__organisation")
 * @ORM\HasLifecycleCallbacks()
 */
class Organisation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    public function __construct()
    {
        $this->individualMembers = new ArrayCollection();
        $this->roles = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function initiateRoles()
    {
        if ($this->roles->count() === 0) {
            $acrole = new ACRole();
            $acrole->setName('ROLE_ADMIN');
            $this->addRole($acrole);
            $acrole = new ACRole();
            $acrole->setName('ROLE_ORG_ADMIN');
            $this->addRole($acrole);
            $acrole = new ACRole();
            $acrole->setName('ROLE_EVENT_ADMIN');
            $this->addRole($acrole);
            $acrole = new ACRole();
            $acrole->setName('ROLE_MSG_ADMIN');
            $this->addRole($acrole);


            $acrole = new ACRole();
            $acrole->setName('ROLE_EVENT_ADMIN');
            $this->addRole($acrole);
            $acrole = new ACRole();
            $acrole->setName('ROLE_MSG_USER');
            $this->addRole($acrole);

            $acrole = new ACRole();
            $acrole->setName('ROLE_USER');
            $this->addRole($acrole);
        }
    }

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Authorisation\IndividualMember", mappedBy="organisation")
     */
    private $individualMembers;

    /**
     * @ORM\Column(type="string", length=191, nullable=true)
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Authorisation\ACRole", mappedBy="organisation", cascade={"persist","merge"})
     */
    private $roles;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): self
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
            $individualMember->setOrganisation($this);
        }

        return $this;
    }

    public function removeIndividualMember(IndividualMember $individualMember): self
    {
        if ($this->individualMembers->contains($individualMember)) {
            $this->individualMembers->removeElement($individualMember);
            // set the owning side to null (unless already changed)
            if ($individualMember->getOrganisation() === $this) {
                $individualMember->setOrganisation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ACRole[]
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(ACRole $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles[] = $role;
            $role->setOrganisation($this);
        }

        return $this;
    }

    public function removeRole(ACRole $role): self
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
            // set the owning side to null (unless already changed)
            if ($role->getOrganisation() === $this) {
                $role->setOrganisation(null);
            }
        }

        return $this;
    }
}
