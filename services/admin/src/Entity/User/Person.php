<?php

namespace App\Entity\User;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Util\User\AppUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiFilter(SearchFilter::class, properties={"uuid": "exact", "nationalities.nricNumber": "exact"})
 * @ORM\Entity(repositoryClass="App\Repository\User\PersonRepository")
 * @ORM\Table(name="user__person")
 * @ORM\HasLifecycleCallbacks()
 */
class Person
{
    const GENDER_MALE = 'MALE';
    const GENDER_FEMALE = 'FEMALE';

    /**
     * @var int|null The Person Id
     * @ORM\Id()
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    public function __construct()
    {
        $this->nationalities = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function combineData()
    {
        $this->name = $this->givenName.' '.$this->middleName.' '.$this->familyName;
    }
    public function copyScalarProperties($person)
    {
        $person->setEmail($this->email);
        $person->setFamilyName($this->familyName);
        $person->setGivenName($this->givenName);
        $person->setBirthDate($this->birthDate);
        $person->setEmployerName($this->employerName);
        $person->setGender($this->gender);
        $person->setJobTitle($this->jobTitle);
        $person->setMiddleName($this->middleName);
        $person->setPhoneNumber($this->phoneNumber);
    }
    public function createNationality($country = null, $nricNumber = null, $passportNumber = null, $uuid = null)
    {
        $nat = new Nationality();
        $this->addNationality($nat);
        $nat->setCountry($country);
        $nat->setNricNumber($nricNumber);
        if (!empty($uuid)) {
            $nat->setUuid($uuid);
        }        $nat->setPassportNumber($passportNumber);
        return $nat;
    }

    public function getName()
    {
        return $this->givenName.' '.$this->familyName;
    }

    /** @return  Nationality|bool */
    public function getNationality()
    {
        return $this->nationalities->first();
    }

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\User\Nationality", mappedBy="person", cascade={"persist","merge"})
     * @Groups({"read","write"})
     */
    private $nationalities;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User\User", inversedBy="person")
     * @ORM\JoinColumn(name="id_user", referencedColumnName="id", onDelete="CASCADE")
     * @Groups("read_user")
     */
    private $user;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $alternateName;
    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"read","write"})
     */
    private $birthDate;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $givenName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $employerName;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $familyName;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $gender;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     * @Groups({"read","write"})
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     * @Groups({"read","write"})
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("read")
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"read","write"})
     */
    private $middleName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read","write"})
     */
    private $jobTitle;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read","write"})
     */
    private $userUuid;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getEmployerName(): ?string
    {
        return $this->employerName;
    }

    public function setEmployerName(?string $employerName): self
    {
        $this->employerName = $employerName;

        return $this;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(string $givenName): self
    {
        $this->givenName = $givenName;

        return $this;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName): self
    {
        $this->familyName = $familyName;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * @return Collection|Nationality[]
     */
    public function getNationalities(): Collection
    {
        return $this->nationalities;
    }

    public function addNationality(Nationality $nationality): self
    {
        if (!$this->nationalities->contains($nationality)) {
            $this->nationalities[] = $nationality;
            $nationality->setPerson($this);
        }

        return $this;
    }

    public function removeNationality(Nationality $nationality): self
    {
        if ($this->nationalities->contains($nationality)) {
            $this->nationalities->removeElement($nationality);
            // set the owning side to null (unless already changed)
            if ($nationality->getPerson() === $this) {
                $nationality->setPerson(null);
            }
        }

        return $this;
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

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): self
    {
        $this->middleName = $middleName;

        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): self
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function getUserUuid(): ?string
    {
        return $this->userUuid;
    }

    public function setUserUuid(string $userUuid): self
    {
        $this->userUuid = $userUuid;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAlternateName(): ?string
    {
        return $this->alternateName;
    }

    public function setAlternateName(?string $alternateName): self
    {
        $this->alternateName = $alternateName;

        return $this;
    }
}
