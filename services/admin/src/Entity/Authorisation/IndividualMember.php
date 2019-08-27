<?php

namespace App\Entity\Authorisation;

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
 * @ORM\Entity(repositoryClass="App\Repository\Authorisation\IndividualMemberRepository")
 * @ORM\Table(name="authorisation__individual_member")
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Authorisation\Organisation", inversedBy="individualMembers")
     * @ORM\JoinColumn(name="id_organisation", referencedColumnName="id")
     */
    private $organisation;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Authorisation\Person", inversedBy="individualMembers")
     * @ORM\JoinColumn(name="id_person", referencedColumnName="id")
     */
    private $person;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Authorisation\ACRole", mappedBy="individualMembers")
     */
    private $aCRoles;

    public function __construct()
    {
        $this->aCRoles = new ArrayCollection();
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
     * @return Collection|ACRole[]
     */
    public function getACRoles(): Collection
    {
        return $this->aCRoles;
    }

    public function addACRole(ACRole $aCRole): self
    {
        if (!$this->aCRoles->contains($aCRole)) {
            $this->aCRoles[] = $aCRole;
            $aCRole->addIndividualMember($this);
        }

        return $this;
    }

    public function removeACRole(ACRole $aCRole): self
    {
        if ($this->aCRoles->contains($aCRole)) {
            $this->aCRoles->removeElement($aCRole);
            $aCRole->removeIndividualMember($this);
        }

        return $this;
    }

}
