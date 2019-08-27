<?php

namespace App\Entity\User;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Doctrine\Types\JsonType;
use App\Util\User\AppUtil;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * ApiResource(
 *     shortName="IndividualMember",
 *     attributes={"access_control"="is_granted('ROLE_USER')"},
 *     collectionOperations={
 *         "get"={"access_control"="is_granted('ROLE_ADMIN')"}
 *     },
 *     itemOperations={
 *         "get"={"access_control"="is_granted('ROLE_USER') and object.user.uuid == user.uuid"}
 *     },
 *     normalizationContext={"groups"={"read"}},
 *     denormalizationContext={"groups"={"write"}}
 * )
 * @ORM\Entity()
 * @ORM\Table(name="user__organisations_users")
 */
class OrganisationUser
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
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @var Organisation|null
     * @ORM\ManyToOne(targetEntity="Organisation", inversedBy="organisationUsers")
     * @ORM\JoinColumn(name="id_organisation", referencedColumnName="id", onDelete="CASCADE")
     */
    private $organisation;

    /**
     * @var User|null
     * @ORM\ManyToOne(targetEntity="User", inversedBy="organisationUsers")
     * @ORM\JoinColumn(name="id_user", referencedColumnName="id", onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=191, nullable=true)
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=191, nullable=true)
     */
    private $accessToken;

    private $organisationUuid;

    private $personUuid;

    /**
     * @ORM\Column(type="magenta_json")
     */
    private $roles = [];

    /**
     * @return mixed
     */
    public function getPersonUuid()
    {
        return $this->personUuid;
    }

    /**
     * @param mixed $personUuid
     */
    public function setPersonUuid(string $personUuid): void
    {
        $this->personUuid = $personUuid;
    }

    /**
     * @return mixed
     */
    public function getOrganisationUuid()
    {
        return $this->organisationUuid;
    }

    /**
     * @param mixed $organisationUuid
     */
    public function setOrganisationUuid(string $organisationUuid): void
    {
        $this->organisationUuid = $organisationUuid;
    }

    /**
     * @return Organisation|null
     */
    public function getOrganisation()
    {
        return $this->organisation;
    }

    /**
     * @param Organisation $organisation
     */
    public function setOrganisation(Organisation $organisation): void
    {
        $this->organisation = $organisation;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
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

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }
}
