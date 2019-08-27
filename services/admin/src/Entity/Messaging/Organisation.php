<?php

namespace App\Entity\Messaging;

use App\Util\Messaging\AppUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Messaging\OrganisationRepository")
 * @ORM\Table(name="messaging__organisation")
 * @ORM\HasLifecycleCallbacks()
 */
class Organisation
{
    private $memberPage;
    private $memberCount;

    /**
     * @var int|null
     * @ORM\Id
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function __construct()
    {
        $this->individualMembers = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->optionSets = new ArrayCollection();
        $this->freeOnMessages = new ArrayCollection();
        $this->roles = new ArrayCollection();
    }

    public function getRole($name)
    {
        /** @var Role $role */
        foreach ($this->roles as $role) {
            if ($role->getName() === $name) {
                return $role;
            }
        }
    }

    public function getIndividualMembersWithMSGAdminRoleGranted()
    {
        $c = Criteria::create();
        $expr = Criteria::expr();
        $c->andWhere($expr->eq('messageAdminGranted', true));
        return $this->individualMembers->matching($c);
    }

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $uuid;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Messaging\Role", mappedBy="organisation", cascade={"persist", "merge"})
     */
    private $roles;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Messaging\IndividualMember", mappedBy="organisation")
     */
    private $individualMembers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Messaging\Message", mappedBy="organisation")
     */
    private $messages;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Messaging\OptionSet", mappedBy="organisation")
     */
    private $optionSets;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $foundedOn;

    public function getIndividualMembersByPage($page = null, $limit = AppUtil::BATCH_SIZE)
    {
        if (empty($this->memberCount)) {
            $this->memberCount = $this->individualMembers->count();
        }

        if (empty($page)) {
            if ($this->memberPage === null) {
                $this->memberPage = 1;
            }
            $page = $this->memberPage;
            if ( ($this->memberPage - 1) * $limit > $this->memberCount) {
                $this->memberPage = $this->memberCount = null;

                return false;
            }
            $this->memberPage++;
        }

        $c = Criteria::create();
        $c->setFirstResult(($page - 1) * $limit);
        $c->setMaxResults($limit);
        return $this->individualMembers->matching($c);
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
     * @return Collection|Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setOrganisation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->contains($message)) {
            $this->messages->removeElement($message);
            // set the owning side to null (unless already changed)
            if ($message->getOrganisation() === $this) {
                $message->setOrganisation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OptionSet[]
     */
    public function getOptionSets(): Collection
    {
        return $this->optionSets;
    }

    public function addOptionSet(OptionSet $optionSet): self
    {
        if (!$this->optionSets->contains($optionSet)) {
            $this->optionSets[] = $optionSet;
            $optionSet->setOrganisation($this);
        }

        return $this;
    }

    public function removeOptionSet(OptionSet $optionSet): self
    {
        if ($this->optionSets->contains($optionSet)) {
            $this->optionSets->removeElement($optionSet);
            // set the owning side to null (unless already changed)
            if ($optionSet->getOrganisation() === $this) {
                $optionSet->setOrganisation(null);
            }
        }

        return $this;
    }


    public function getFoundedOn(): ?\DateTimeInterface
    {
        return $this->foundedOn;
    }

    public function setFoundedOn(?\DateTimeInterface $foundedOn): self
    {
        $this->foundedOn = $foundedOn;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Collection|Role[]
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles[] = $role;
            $role->setOrganisation($this);
        }

        return $this;
    }

    public function removeRole(Role $role): self
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
