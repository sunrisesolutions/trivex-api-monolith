<?php

namespace App\Entity\User;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Role;
use App\Entity\Person\Person;
use App\Util\AppUtil;
use App\Util\AwsS3Util;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Organisation\Organisation;
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
    const ROLE_ADMIN = 'ROLE_ADMIN';

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
    }

    public function isGranted($permission = 'ALL', $object = null, $class = null, IndividualMember $member = null, Organisation $org = null)
    {

    }

    /**
     * @return ArrayCollection
     */
    public function getAdminOrganisations()
    {
        $orgs = new ArrayCollection();
        /** @var IndividualMember $organisationUser */
        foreach ($this->getIndividualMembers() as $organisationUser) {
            if (in_array(Role::ROLE_ORGANISATION_ADMIN, $this->getRoles())) {
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
        /** @var IndividualMember $im */
        foreach ($this->getIndividualMembers() as $im) {
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
     * @ORM\PreUpdate
     */
    public function preSave()
    {
        if (!empty($this->person)) {
            $nat = $this->person->getNationality();
//            $idNumber = $nat->getNricNumber();
            $this->idNumber = $nat->getNricNumber();
            $this->birthDate = $this->person->getBirthDate();
            $this->phone = $this->person->getPhoneNumber();
        }
    }

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid('USER');
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

    /** @return IndividualMember */
    public function findIndividualMemberByUuid($uuid)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('uuid', $uuid))
//            ->orderBy(array('username' => Criteria::ASC))
            ->setFirstResult(0)
            ->setMaxResults(1);

        return $this->getIndividualMembers()->matching($criteria)->first();
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

    public function fixAdminRole(){
//        $roles = $this->getRoles();
//        if (array_search(Role::ROLE_ORGANISATION_ADMIN, $roles)) {
//            if (!array_search(self::ROLE_ADMIN, $roles)) {
////                $this->roles[] = self::ROLE_ADMIN;
//            }
//        }
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        foreach ($this->getIndividualMembers() as $im) {
            if (!empty($im->getRoles())) {
                /** @var Role $r */
                foreach ($im->getRoles() as $r) {
                    if ($r != null && !in_array($r, $roles)) $roles[] = $r->getName();
                }
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * @var Person $person
     * @ORM\OneToOne(targetEntity="App\Entity\Person\Person", mappedBy="user", cascade={"persist","merge"})
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
    public function getIndividualMembers(): \Doctrine\Common\Collections\Collection
    {
        if (empty($this->person) || empty($members = $this->person->getIndividualMembers())) {
            return new ArrayCollection();
        }
        return $members;
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
