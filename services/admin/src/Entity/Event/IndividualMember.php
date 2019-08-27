<?php

namespace App\Entity\Event;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * ApiResource(
 *     collectionOperations={
 *         "get"={"access_control"="is_granted('ROLE_USER')"},
 *     },
 *     normalizationContext={"groups"={"read"}},
 *     denormalizationContext={"groups"={"write"}}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\Event\IndividualMemberRepository")
 * @ORM\Table(name="event__individual_member")
 * @ORM\HasLifecycleCallbacks()
 */
class IndividualMember
{
    /**
     * @var int|null The User Id
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Groups("read")
     * @ORM\Column(type="string", length=191, nullable=true)
     */
    private $uuid;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Event\Organisation", inversedBy="individualMembers")
     * @ORM\JoinColumn(name="id_organisation", referencedColumnName="id")
     */
    private $organisation;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Event\Person", inversedBy="individualMembers")
     * @ORM\JoinColumn(name="id_person", referencedColumnName="id")
     */
    private $person;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Event\Registration", mappedBy="individualMember")
     */
    private $registrations;

    public function __construct()
    {
        $this->registrations = new ArrayCollection();
    }

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

    public function getOrganisation(): ?Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(?Organisation $organisation): self
    {
        $this->organisation = $organisation;

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

    /**
     * @return Collection|Registration[]
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function addRegistration(Registration $registration): self
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations[] = $registration;
            $registration->setIndividualMember($this);
        }

        return $this;
    }

    public function removeRegistration(Registration $registration): self
    {
        if ($this->registrations->contains($registration)) {
            $this->registrations->removeElement($registration);
            // set the owning side to null (unless already changed)
            if ($registration->getIndividualMember() === $this) {
                $registration->setIndividualMember(null);
            }
        }

        return $this;
    }

}
