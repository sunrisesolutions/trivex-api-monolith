<?php

namespace App\Entity\Organisation;

use Doctrine\ORM\Mapping as ORM;
use App\Util\Organisation\AppUtil;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Organisation\NationalityRepository")
 * @ORM\Table(name="organisation__nationality")
 */
class Nationality
{
    /**
     * @var int|null The Event Id
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $country = 'Singapore';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nricNumber;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $passportNumber;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Organisation\Person", inversedBy="nationalities")
     * @ORM\JoinColumn(name="id_person", referencedColumnName="id", onDelete="CASCADE")
     */
    private $person;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $uuid;

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid();
        }
    }

    public function copyScalarProperties($dest)
    {
        if(!empty($this->uuid)){
            $dest->setUuid($this->uuid);
        }
        $dest->setPassportNumber($this->passportNumber);
        $dest->setNricNumber($this->nricNumber);
        $dest->setCountry($this->country);
    }

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getNricNumber(): ?string
    {
        return $this->nricNumber;
    }

    public function setNricNumber(?string $nricNumber): self
    {
        $this->nricNumber = $nricNumber;

        return $this;
    }

    public function getPassportNumber(): ?string
    {
        return $this->passportNumber;
    }

    public function setPassportNumber(?string $passportNumber): self
    {
        $this->passportNumber = $passportNumber;

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

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

}
