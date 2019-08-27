<?php

namespace App\Entity\User;

use App\Util\User\AppUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user__organisation")
 * @ORM\HasLifecycleCallbacks()
 */
class Organisation
{
    /**
     * @var int|null The User Id
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @return User */
    public function findUserByAccessToken($accessToken){
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('accessToken',$accessToken))
//            ->orderBy(array('username' => Criteria::ASC))
            ->setFirstResult(0)
            ->setMaxResults(1);

        /** @var OrganisationUser $ou */
        if(empty($ou = $this->organisationUsers->matching($criteria)->first())){
            return null;
        }
        return $ou->getUser();
    }

    public function __construct()
    {
        $this->organisationUsers = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $name;

    /**
     * @var string The Universally Unique Id
     * @ORM\Column(type="string", length=191, unique=true)
     * @Assert\NotBlank()
     */
    private $uuid;

    /**
     * @var string code|null
     * @ORM\Column(type="string",nullable=true)
     * @Assert\NotBlank()
     */
    private $code;
    /**
     * @ORM\Column(type="string", length=64, nullable=true)

     */
    private $type;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)

     */
    private $subdomain;

    /**
     * @ORM\OneToMany(
     *     targetEntity="OrganisationUser",
     *     mappedBy="organisation", cascade={"persist"}, orphanRemoval=true
     * )
     *
     * @var \Doctrine\Common\Collections\Collection ;
     */
    private $organisationUsers;

    public function addOrganisationUser(OrganisationUser $orgUser)
    {
        $this->organisationUsers->add($orgUser);
        $orgUser->setOrganisation($this);
    }

    public function removeOrganisationUser(OrganisationUser $orgUser)
    {
        $this->organisationUsers->removeElement($orgUser);
        $orgUser->setOrganisation(null);
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
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string|null $code
     */
    public function setCode(?string $code): void
    {
        $this->code = $code;
    }  public function getName(): ?string
{
    return $this->name;
}

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }    public function getType(): ?string
{
    return $this->type;
}

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }


    public function getSubdomain(): ?string
    {
        return $this->subdomain;
    }

    public function setSubdomain(?string $subdomain): self
    {
        $this->subdomain = $subdomain;

        return $this;
    }
}
