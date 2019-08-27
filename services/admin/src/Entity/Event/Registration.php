<?php

namespace App\Entity\Event;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Util\Event\AppUtil;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}},
 *     denormalizationContext={"groups"={"write"}}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\Event\RegistrationRepository")
 * @ORM\Table(name="event__registration")
 * @ORM\HasLifecycleCallbacks()
 */
class Registration
{
    const GENDER_MALE = 'MALE';
    const GENDER_FEMALE = 'FEMALE';

    const ATTENDEE_INDIVIDUAL = 'INDIVIDUAL_MEMBER';
    const ATTENDEE_NON_MEMBER_INDIVIDUAL = 'INDIVIDUAL_NON_MEMBER';

    const LOCATION_ONLINE = 'ONLINE';
    const LOCATION_VENUE = 'VENUE';

    /**
     * @var int|null The Event Id
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Event\Attendee", inversedBy="registration", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_attendee", referencedColumnName="id")
     * @ApiSubresource()
     */
    private $attendee;

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid(AppUtil::APP_NAME.'_REG');
        }
    }

    /**
     * @ORM\PrePersist
     */
    public function initiateAccessToken()
    {
        if (empty($this->accessToken)) {
            $this->accessToken = AppUtil::generateUuid($this->event->getId());
        }
    }

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("read")
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=64, options={"default": "INDIVIDUAL_MEMBER"})
     */
    private $attendeeType = self::ATTENDEE_INDIVIDUAL;

    /**
     * @ORM\Column(type="string", length=64, options={"default": "VENUE"})
     */
    private $locationType = self::LOCATION_VENUE;

    /**
     * @var Event
     * @ORM\ManyToOne(targetEntity="App\Entity\Event\Event", inversedBy="registrations")
     * @ORM\JoinColumn(name="id_event", referencedColumnName="id")
     * @Groups({"read", "write"})
     */
    private $event;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Event\Person", inversedBy="registrations")
     * @ORM\JoinColumn(name="id_person", referencedColumnName="id")
     */
    private $person;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"read", "write"})
     */
    private $middleName;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"read", "write"})
     */
    private $birthDate;

    /**
     * @ORM\Column(type="string", length=64)
     * @Groups({"read", "write"})
     */
    private $givenName;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"read", "write"})
     */
    private $familyName;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     * @Groups({"read", "write"})
     */
    private $gender;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     * @Groups({"read", "write"})
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email."
     * )
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     * @Groups({"read", "write"})
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write"})
     */
    private $memberUuid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read", "write"})
     */
    private $accessToken;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Event\IndividualMember", inversedBy="registrations")
     */
    private $individualMember;

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

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): self
    {
        $this->person = $person;

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

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): self
    {
        $this->middleName = $middleName;

        return $this;
    }

    public function getAttendee(): ?Attendee
    {
        return $this->attendee;
    }

    public function setAttendee(?Attendee $attendee): self
    {
        $this->attendee = $attendee;

        return $this;
    }

    public function setMemberUuid(string $memberUuid): self
    {
        $this->memberUuid = $memberUuid;

        return $this;
    }


    public function getMemberUuid(): ?string
    {
        return $this->memberUuid;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }


    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getAttendeeType(): ?string
    {
        return $this->attendeeType;
    }

    public function setAttendeeType(string $type): self
    {
        $this->attendeeType = $type;

        return $this;
    }

    public function getLocationType(): ?string
    {
        return $this->locationType;
    }

    public function setLocationType(string $type): self
    {
        $this->locationType = $type;

        return $this;
    }

    public function getIndividualMember(): ?IndividualMember
    {
        return $this->individualMember;
    }

    public function setIndividualMember(?IndividualMember $individualMember): self
    {
        $this->individualMember = $individualMember;

        return $this;
    }

}
