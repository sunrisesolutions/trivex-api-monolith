<?php

namespace App\Entity\Event;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\Person\Person;
use App\Util\AppUtil;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get"={"access_control"="is_granted('ROLE_USER')"},
 *         "post"
 *     },
 *     normalizationContext={"groups"={"read"}},
 *     denormalizationContext={"groups"={"write"}}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\Event\AttendeeRepository")
 * @ORM\Table(name="event__attendee")
 * @ORM\HasLifecycleCallbacks()
 */
class Attendee
{
    /**
     * @var int|null The Event Id
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getMemberYNLbl()
    {
        return empty($this->registration->getIndividualMember()) ? 'No' : 'Yes';
    }

    public function getName()
    {
        return $this->registration->getGivenName().' '.$this->registration->getFamilyName();
    }

    public function getGender()
    {
        return $this->registration->getGender();
    }

    public function getEmail()
    {
        return $this->registration->getEmail();
    }

    public function getPhoneNumber()
    {
        return $this->registration->getPhoneNumber();
    }

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid('ATTENDEE');
        }
    }

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("read")
     */
    private $uuid;

    /**
     * @var Registration|null $registration
     * @ORM\OneToOne(targetEntity="App\Entity\Event\Registration", mappedBy="attendee")
     * @ORM\JoinColumn(name="id_registration", referencedColumnName="id", onDelete="CASCADE")
     * @Groups({"read", "write"})
     */
    private $registration;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

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

    public function getRegistration(): ?Registration
    {
        return $this->registration;
    }

    public function setRegistration(?Registration $registration): self
    {
        $this->registration = $registration;

        // set (or unset) the owning side of the relation if necessary
        $newAttendee = null === $registration ? null : $this;
        if ($newAttendee !== $registration->getAttendee()) {
            $registration->setAttendee($newAttendee);
        }

        return $this;
    }
}
