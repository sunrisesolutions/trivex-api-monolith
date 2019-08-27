<?php

namespace App\Entity\Organisation;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Organisation\PersonRepository")
 * @ORM\Table(name="organisation__person")
 * @ORM\HasLifecycleCallbacks()
 */
class Person
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

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
        $person->setUuid($this->uuid?:'');
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
        }
        $nat->setPassportNumber($passportNumber);
        return $nat;
    }

    /**
     * @var string|null
     * @ORM\Column(type="string", length=191, nullable=true)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $alternateName;
    /**
     * @ORM\Column(type="string", length=191)
     */
    private $uuid;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Organisation\IndividualMember", mappedBy="person")
     */
    private $individualMembers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Organisation\Nationality", cascade={"persist", "merge"} , mappedBy="person")
     */
    private $nationalities;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $birthDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $jobTitle;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $employerName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $givenName;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $middleName;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $familyName;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $gender;

    public function __construct()
    {
        $this->individualMembers = new ArrayCollection();
        $this->nationalities = new ArrayCollection();
    }

    /** @return  Nationality|bool */
    public function getNationality()
    {
        $nat = $this->nationalities->first();
        if (empty($nat)) {
            $nat = new Nationality();
            $this->addNationality($nat);
        }
        return $nat;
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
            $individualMember->setPerson($this);
        }

        return $this;
    }

    public function removeIndividualMember(IndividualMember $individualMember): self
    {
        if ($this->individualMembers->contains($individualMember)) {
            $this->individualMembers->removeElement($individualMember);
            // set the owning side to null (unless already changed)
            if ($individualMember->getPerson() === $this) {
                $individualMember->setPerson(null);
            }
        }

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

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): self
    {
        $this->jobTitle = $jobTitle;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
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

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     */
    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }


    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $salutation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $homeAddress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $homePostalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $residentCountry;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mobileNumber;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $maritalStatus;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $academicInfo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $yearsInPosition;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $jobFunction;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $alternateEmployerName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $jobIndustry;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $employerAddress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $employerPostalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $employerCountry;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $employerContact;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $interestGroups;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lifeStyle;

    public function getSalutation(): ?string
    {
        return $this->salutation;
    }

    public function setSalutation(?string $salutation): self
    {
        $this->salutation = $salutation;

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


    public function getAlternateName(): ?string
    {
        return $this->alternateName;
    }

    public function setAlternateName(?string $alternateName): self
    {
        $this->alternateName = $alternateName;

        return $this;
    }

    public function getHomeAddress(): ?string
    {
        return $this->homeAddress;
    }

    public function setHomeAddress(?string $homeAddress): self
    {
        $this->homeAddress = $homeAddress;

        return $this;
    }

    public function getHomePostalCode(): ?string
    {
        return $this->homePostalCode;
    }

    public function setHomePostalCode(?string $homePostalCode): self
    {
        $this->homePostalCode = $homePostalCode;

        return $this;
    }

    public function getResidentCountry(): ?string
    {
        return $this->residentCountry;
    }

    public function setResidentCountry(?string $residentCountry): self
    {
        $this->residentCountry = $residentCountry;

        return $this;
    }

    public function getMobileNumber(): ?string
    {
        return $this->mobileNumber;
    }

    public function setMobileNumber(?string $mobileNumber): self
    {
        $this->mobileNumber = $mobileNumber;

        return $this;
    }

    public function getMaritalStatus(): ?string
    {
        return $this->maritalStatus;
    }

    public function setMaritalStatus(?string $maritalStatus): self
    {
        $this->maritalStatus = $maritalStatus;

        return $this;
    }

    public function getAcademicInfo(): ?string
    {
        return $this->academicInfo;
    }

    public function setAcademicInfo(?string $academicInfo): self
    {
        $this->academicInfo = $academicInfo;

        return $this;
    }

    public function getYearsInPosition(): ?int
    {
        return $this->yearsInPosition;
    }

    public function setYearsInPosition(?int $yearsInPosition): self
    {
        $this->yearsInPosition = $yearsInPosition;

        return $this;
    }

    public function getJobFunction(): ?string
    {
        return $this->jobFunction;
    }

    public function setJobFunction(?string $jobFunction): self
    {
        $this->jobFunction = $jobFunction;

        return $this;
    }

    public function getAlternateEmployerName(): ?string
    {
        return $this->alternateEmployerName;
    }

    public function setAlternateEmployerName(?string $alternateEmployerName): self
    {
        $this->alternateEmployerName = $alternateEmployerName;

        return $this;
    }

    public function getJobIndustry(): ?string
    {
        return $this->jobIndustry;
    }

    public function setJobIndustry(?string $jobIndustry): self
    {
        $this->jobIndustry = $jobIndustry;

        return $this;
    }

    public function getEmployerAddress(): ?string
    {
        return $this->employerAddress;
    }

    public function setEmployerAddress(?string $employerAddress): self
    {
        $this->employerAddress = $employerAddress;

        return $this;
    }

    public function getEmployerPostalCode(): ?string
    {
        return $this->employerPostalCode;
    }

    public function setEmployerPostalCode(?string $employerPostalCode): self
    {
        $this->employerPostalCode = $employerPostalCode;

        return $this;
    }

    public function getEmployerCountry(): ?string
    {
        return $this->employerCountry;
    }

    public function setEmployerCountry(?string $employerCountry): self
    {
        $this->employerCountry = $employerCountry;

        return $this;
    }

    public function getEmployerContact(): ?string
    {
        return $this->employerContact;
    }

    public function setEmployerContact(?string $employerContact): self
    {
        $this->employerContact = $employerContact;

        return $this;
    }

    public function getInterestGroups(): ?string
    {
        return $this->interestGroups;
    }

    public function setInterestGroups(?string $interestGroups): self
    {
        $this->interestGroups = $interestGroups;

        return $this;
    }

    public function getLifeStyle(): ?string
    {
        return $this->lifeStyle;
    }

    public function setLifeStyle(?string $lifeStyle): self
    {
        $this->lifeStyle = $lifeStyle;

        return $this;
    }
}
