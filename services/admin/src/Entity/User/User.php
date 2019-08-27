<?php

namespace App\Entity\User;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

use App\Util\User\AppUtil;
use App\Util\User\AwsS3Util;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User\OrganisationUser;
use App\Entity\User\Organisation;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(
 *     attributes={"access_control"="is_granted('ROLE_USER')"},
 *     collectionOperations={
 *         "get",
 *         "post"={"access_control"="is_granted('ROLE_ADMIN') or is_granted('ROLE_ORG_ADMIN')"}
 *     },
 *     normalizationContext={"groups"={"read"}},
 *     denormalizationContext={"groups"={"write"}}
 * )
 * @ApiFilter(SearchFilter::class, properties={"email": "exact", "username": "exact", "uuid": "exact"})
 * @ORM\Entity(repositoryClass="App\Repository\User\UserRepository")
 * @ORM\Table(name="user__user")
 * @ORM\HasLifecycleCallbacks()
 */
class User implements UserInterface
{
    const TTL = 1800;
    const ROLE_ORG_ADMIN = 'ROLE_ORG_ADMIN';

    /**
     * @var int|null The User Id
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->organisationUsers = new ArrayCollection();
    }

    public function isGranted($permission = 'ALL', $object = null, $class = null, OrganisationUser $member = null, Organisation $org = null)
    {

    }

    /**
     * @return ArrayCollection
     */
    public function getAdminOrganisations()
    {
        $orgs = new ArrayCollection();
        /** @var OrganisationUser $organisationUser */
        foreach ($this->organisationUsers as $organisationUser) {
            if (in_array(self::ROLE_ORG_ADMIN, $this->getRoles())) {
                $orgs->add($organisationUser->getOrganisation());
            }
        }
        return $orgs;
    }

    /**
     * @return array
     * @Groups("read")
     */
    public function getIndividualMemberData()
    {
        $data = [];
        /** @var OrganisationUser $im */
        foreach ($this->organisationUsers as $im) {
            $member['accessToken'] = $im->getAccessToken();
            $member['id'] = $im->getId();
            $member['uuid'] = $im->getUuid();
            $member['roles'] = $im->getRoles();
            $data[] = $member;
        }
        return $data;
    }

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid();
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function initiateData()
    {
        if (empty($this->roles)) {
            $this->roles[] = 'ROLE_USER';
        }
    }

    /** @return OrganisationUser */
    public function findOrgUserByUuid($uuid)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('uuid', $uuid))
//            ->orderBy(array('username' => Criteria::ASC))
            ->setFirstResult(0)
            ->setMaxResults(1);

        return $this->organisationUsers->matching($criteria)->first();
    }

    /**
     * File name of the profile picture.
     *
     * @ORM\Column(type="string", length=25, nullable=true)
     * @Groups({"read", "write"})
     */
    private $pictureName;

    public function getPictureName(): ?string
    {
        return $this->pictureName;
    }

    public function setPictureName(?string $pictureName): self
    {
        if (empty($pictureName) && !empty($this->pictureName)) {
            AwsS3Util::getInstance()->deleteObject($this->buildProfilePicturePath());
        }
        $this->pictureName = $pictureName;
        return $this;
    }

    private function buildProfilePicturePath()
    {
        return 'user/photo/profile/'.$this->uuid;
    }

    /**
     * @Groups({"read"})
     * @return mixed|string|null
     */
    public function getProfilePictureWriteForm()
    {
        $path = $this->buildProfilePicturePath();
        return array_merge(['filePath' => AwsS3Util::getInstance()->getConfig()['directory'].'/'.$path], AwsS3Util::getInstance()->getObjectWriteForm($path));
    }

    /**
     * @Groups({"read"})
     * @return mixed|string|null
     */
    public function getProfilePictureReadUrl()
    {
        if (empty($this->pictureName)) {
            return null;
        }
        $path = $this->buildProfilePicturePath();
        return AwsS3Util::getInstance()->getObjectReadUrl($path);
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        foreach ($this->organisationUsers as $im) {
            if (!empty($im->getRoles())) {
                foreach ($im->getRoles() as $r) {
                    if ($r != null && !in_array($r, $roles)) $roles[] = $r;
                }
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * @ORM\OneToMany(
     *     targetEntity="OrganisationUser",
     *     mappedBy="user", cascade={"persist"}, orphanRemoval=true
     * )
     *
     * @var \Doctrine\Common\Collections\Collection ;
     */
    private $organisationUsers;

    public function addOrganisationUser(OrganisationUser $orgUser)
    {
        $this->organisationUsers->add($orgUser);
        $orgUser->setUser($this);
    }

    public function removeOrganisationUser(OrganisationUser $orgUser)
    {
        $this->organisationUsers->removeElement($orgUser);
        $orgUser->setUser(null);
    }

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User\Person", mappedBy="user", cascade={"persist","merge"})
     */
    private $person;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email."
     * )
     */
    private $email;

    /**
     * @var array
     * @ORM\Column(type="magenta_json")
     */
    private $roles = [];

    /**
     * @var string The Universally Unique Id
     * @ORM\Column(type="string", length=191, unique=true)
     * @Groups({"read"})
     */
    private $uuid;

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @var string|null
     * @Groups({"write"})
     */
    private $plainPassword;

    /**
     * @var string|null Login username
     * @Groups({"read", "write"})
     * @ORM\Column(nullable=true, unique=true, length=128)
     */
    private $username = '';
    /**
     * @var string|null Login with ID Number (NRIC)
     * @Groups({"read", "write"})
     * @ORM\Column(nullable=true)
     */
    private $idNumber = '';

    /**
     * @var string|null Login with phone number
     * @Groups({"read", "write"})
     * @ORM\Column(nullable=true)
     */
    private $phone = '';

    /**
     * @var \DateTime|null Login with DOB
     * @Groups({"read", "write"})
     * @ORM\Column(type="date", nullable=true)
     */
    private $birthDate;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     */
    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string|null
     */
    public function getIdNumber(): ?string
    {
        return $this->idNumber;
    }

    /**
     * @param string|null $idNumber
     */
    public function setIdNumber(?string $idNumber): void
    {
        $this->idNumber = $idNumber;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     */
    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return \DateTime|null
     */
    public function getBirthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    /**
     * @param \DateTime|null $birthDate
     */
    public function setBirthDate(?\DateTime $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime|null $createdAt
     */
    public function setCreatedAt(?\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganisationUsers(): \Doctrine\Common\Collections\Collection
    {
        return $this->organisationUsers;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection $organisationUsers
     */
    public function setOrganisationUsers(\Doctrine\Common\Collections\Collection $organisationUsers): void
    {
        $this->organisationUsers = $organisationUsers;
    }

    /**
     * @return string|null
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $plainPassword
     */
    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function setPerson(?Person $person): self
    {
        $this->person = $person;

        // set (or unset) the owning side of the relation if necessary
        $newUser = null === $person ? null : $this;
        if ($newUser !== $person->getUser()) {
            $person->setUser($newUser);
        }

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }
}
