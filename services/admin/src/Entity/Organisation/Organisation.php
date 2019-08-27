<?php

namespace App\Entity\Organisation;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Util\Organisation\AppUtil;
use App\Util\Organisation\AwsS3Util;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={"access_control"="is_granted('ROLE_USER')"},
 *     collectionOperations={
 *         "get"={},
 *         "post"={"access_control"="is_granted('ROLE_ADMIN')"}
 *     },
 *     normalizationContext={"groups"={"read"}},
 *     denormalizationContext={"groups"={"write"}}
 * )
 * @ApiFilter(SearchFilter::class, properties={"uuid": "exact", "subdomain": "exact", "code": "exact"})
 * @ORM\Entity(repositoryClass="App\Repository\Organisation\OrganisationRepository")
 * @ORM\Table(name="organisation__organisation")
 * @ORM\HasLifecycleCallbacks()
 */
class Organisation
{
    const TYPE_COMPANY = 'COMPANY';

    /**
     * @var int|null The Event Id
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    private function buildLogoPath()
    {
        return 'organisation/logo/'.$this->uuid;
    }

    public function setLogoName(?string $logoName): self
    {
        if (empty($logoName) && !empty($this->logoName)) {
            AwsS3Util::getInstance()->deleteObject($this->buildLogoPath());
        }

        $this->logoName = $logoName;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid();
            if (empty($this->code)) {
                $this->code = $this->uuid;
            }
        }
    }

    public function copyScalarProperties($org)
    {
        $org->setName($this->name);
        $org->setAddress($this->address);
        if (!empty($this->uuid)) {
            $org->setUuid($this->uuid);
        }
//        $org->setFoundedOn($this->foundedOn);
//        $org->setLogoName($this->logoName);
//        $org->setRegistrationNumber($this->registrationNumber);
        if (method_exists($org, 'setSubdomain')) {
            $org->setSubdomain($this->subdomain);
        }
        if (method_exists($org, 'setType')) {
            $org->setType($this->type);
        }
        if (method_exists($org, 'setCode')) {
            $org->setCode($this->code);
        }
    }

    /**
     * @ORM\PrePersist
     */
    public function initiateRoles()
    {
        if ($this->roles->count() === 0) {
            $acrole = new Role();
            $acrole->setName('ROLE_ORG_ADMIN');
            $this->addRole($acrole);

            $acrole = new Role();
            $acrole->setName('ROLE_EVENT_ADMIN');
            $this->addRole($acrole);

            $acrole = new Role();
            $acrole->setName('ROLE_MSG_ADMIN');
            $this->addRole($acrole);

            $acrole = new Role();
            $acrole->setName('ROLE_MSG_USER');
            $this->addRole($acrole);
        }
    }

    /**
     * @Groups({"read"})
     *
     * @return mixed|string|null
     */
    public function getLogoWriteForm()
    {
        $path = $this->buildLogoPath();

        return array_merge(['filePath' => AwsS3Util::getInstance()->getConfig()['directory'].'/'. $path], AwsS3Util::getInstance()->getObjectWriteForm($path));
    }

    /**
     * @Groups({"read"})
     *
     * @return mixed|string|null
     */
    public function getLogoReadUrl()
    {
        if (empty($this->logoName)) {
            return null;
        }
        $path = $this->buildLogoPath();

        return AwsS3Util::getInstance()->getObjectReadUrl($path);
    }

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"read"})
     */
    private $uuid;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"read", "write"})
     */
    private $foundedOn;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"read", "write"})
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write"})
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=128)
     * @Groups({"read", "write"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $registrationNumber;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Organisation\Organisation", inversedBy="children")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Organisation\Organisation", mappedBy="parent")
     */
    private $children;

    /**
     * File name of the logo.
     *
     * @ORM\Column(type="string", length=25, nullable=true)
     * @Groups({"read", "write"})
     */
    private $logoName;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Organisation\IndividualMember", mappedBy="organisation")
     * @ApiSubresource()
     */
    private $individualMembers;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"read", "write"})
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     * @Groups({"read", "write"})
     */
    private $subdomain;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Organisation\Role", mappedBy="organisation", cascade={"persist", "merge"})
     */
    private $roles;

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateTs() {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean", options={"default":true})
     */
    private $networkingEnabled = true;

    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $freeonMessagingEnabled = false;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $eventEnabled = true;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $adminAnnouncementEnabled = true;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $memberAnnouncementEnabled = true;

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->individualMembers = new ArrayCollection();
        $this->roles = new ArrayCollection();
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

    public function getFoundedOn(): ?\DateTimeInterface
    {
        return $this->foundedOn;
    }

    public function setFoundedOn(?\DateTimeInterface $foundedOn): self
    {
        $this->foundedOn = $foundedOn;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(?string $registrationNumber): self
    {
        $this->registrationNumber = $registrationNumber;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getLogoName(): ?string
    {
        return $this->logoName;
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getSubdomain(): ?string
    {
        return $this->subdomain;
    }

    public function setSubdomain(?string $subdomain): self
    {
        $this->subdomain = $subdomain;

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

    public function getNetworkingEnabled(): ?bool
    {
        return $this->networkingEnabled;
    }

    public function setNetworkingEnabled(bool $networkingEnabled): self
    {
        $this->networkingEnabled = $networkingEnabled;

        return $this;
    }

    public function getFreeonMessagingEnabled(): ?bool
    {
        return $this->freeonMessagingEnabled;
    }

    public function setFreeonMessagingEnabled(bool $freeonMessagingEnabled): self
    {
        $this->freeonMessagingEnabled = $freeonMessagingEnabled;

        return $this;
    }

    public function getEventEnabled(): ?bool
    {
        return $this->eventEnabled;
    }

    public function setEventEnabled(bool $eventEnabled): self
    {
        $this->eventEnabled = $eventEnabled;

        return $this;
    }

    public function getAdminAnnouncementEnabled(): ?bool
    {
        return $this->adminAnnouncementEnabled;
    }

    public function setAdminAnnouncementEnabled(bool $adminAnnouncementEnabled): self
    {
        $this->adminAnnouncementEnabled = $adminAnnouncementEnabled;

        return $this;
    }

    public function getMemberAnnouncementEnabled(): ?bool
    {
        return $this->memberAnnouncementEnabled;
    }

    public function setMemberAnnouncementEnabled(bool $memberAnnouncementEnabled): self
    {
        $this->memberAnnouncementEnabled = $memberAnnouncementEnabled;

        return $this;
    }
}
