<?php

namespace App\Entity\Organisation;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Entity\Messaging\Delivery;
use App\Entity\Messaging\Message;
use App\Entity\Messaging\NotifSubscription;
use App\Entity\Person\Person;
use App\Entity\Event\Registration;
use App\Filter\Organisation\ConnectedToMemberUuidFilter;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Util\AppUtil;
use App\Util\AwsS3Util;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Controller\Organisation\SendEmailToIndividualMember;
//*     attributes={"access_control"="is_granted('ROLE_USER')"},
//*     "put"={"access_control"="is_granted('ROLE_USER')"},
/**
 * @ApiResource(

 *     collectionOperations={
 *         "get",
 *         "post"={"access_control"="is_granted('ROLE_ORG_ADMIN')"},
 *     },
 *     itemOperations={
 *     "get",
 *     "delete"={"access_control"="is_granted('ROLE_ORG_ADMIN')"},
 *     "put",
 *     "put_email"={
 *         "method"="PUT",
 *         "path"="/individual_members/{id}/email",
 *         "controller"=SendEmailToIndividualMember::class,
 *         "normalization_context"={"groups"={"email"}},
 *         "denormalization_context"={"groups"={"email"}},
 *     }
 *     },
 *     normalizationContext={"groups"={"read_member"}},
 *     denormalizationContext={"groups"={"write"}}
 * )
 * @ApiFilter(SearchFilter::class, properties={"uuid": "exact", "fulltextString": "partial"})
 * @ApiFilter(ConnectedToMemberUuidFilter::class)
 *
 * @ORM\Entity(repositoryClass="App\Repository\Organisation\IndividualMemberRepository")
 * @ORM\Table(name="organisation__individual_member")
 * @ORM\HasLifecycleCallbacks()
 */
class IndividualMember
{
    const TYPE_SUBSCRIPTION = 'SUBSCRIPTION';

    private $messageDeliveryCache = [];

    /**
     * @var int|null The Event Id
     * @Groups({"read_member","read"})
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null
     * @Groups({"email"})
     */
    public $emailBody;

    /**
     * @var string|null
     * @Groups({"email"})
     */
    public $emailSubject;

    /**
     * File name of the profilePicture.
     *
     * @ORM\Column(type="string", length=25, nullable=true)
     * @Groups({"read_member", "write"})
     */
    private $profilePicture;

    /**
     * Member Email
     * @var string|null $email
     * @ORM\Column(type="string", length=128, nullable=true)
     * @Groups({"read_member", "write"})
     */
    private $email;


    /**
     * @Groups({"read_member"})
     *
     * @return mixed|string|null
     */
    public function getProfilePictureWriteForm()
    {
        $path = $this->buildProfilePicturePath();

        return array_merge(['filePath' => AwsS3Util::getInstance()->getConfig()['directory'].'/'.$path], AwsS3Util::getInstance()->getObjectWriteForm($path));
    }

    private function buildProfilePicturePath()
    {
        return sprintf('organisation/individual/profile-picture/ORG_IM-UUID-%s.jpg', $this->uuid);
    }

    public function setProfilePicture(?string $profilePicture): self
    {
        if (empty($profilePicture) && !empty($this->profilePicture)) {
            AwsS3Util::getInstance()->deleteObject($this->buildProfilePicturePath());
        }

        $this->profilePicture = $profilePicture;

        return $this;
    }

    /**
     * @Groups({"read"})
     *
     * @return mixed|string|null
     */
    public function getProfilePictureReadUrl()
    {
        if (empty($this->profilePicture)) {
            return null;
        }
        $path = $this->buildProfilePicturePath();

        return AwsS3Util::getInstance()->getObjectReadUrl($path);
    }

    /**
     * @Groups({"read_member"})
     * @return mixed|string
     */
    public function getProfilePicture()
    {
//        return AwsS3Util::getInstance()->getObjectReadUrl(sprintf('organisation/individual/profile-picture/ORG_IM-UUID-%d.jpg', $this->id));
        return $this->getProfilePictureReadUrl();
    }

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid('MEMBER');
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function fixData()
    {
        $foundMsgAdmin = false;
        /** @var Role $role */
        foreach ($this->roles as $role) {
            if ($role->getName() === 'ROLE_MSG_ADMIN' or $role->getName() === 'ROLE_ORG_ADMIN') {
                $foundMsgAdmin = true;
                break;
            }
        }
        $this->messageAdminGranted = $foundMsgAdmin;
//        if (empty($this->optionsSelectedAt) && !empty($this->selectedOptions)) {
//            $this->optionsSelectedAt = new \DateTime();
//        }
    }

    /**
     * @return bool
     * @Groups("read_member")
     */
    public function isMessageDeliverable(): bool
    {
        return !empty($this->getMessageDeliverable());
    }

    public function getMessageDeliverable()
    {
        $c = Criteria::create();
        $expr = Criteria::expr();
        $c->andWhere($expr->eq('name', 'ROLE_MSG_USER'));
        $this->messageDeliverable = $this->roles->matching($c)->count() > 0;
        return $this->messageDeliverable;
    }

    /**
     * @return bool
     * @Groups("read_member")
     */
    public function isAdmin(): bool
    {
        return !empty($this->getAdmin());
    }

    public function getAdmin(): ?bool
    {
        $c = Criteria::create();
        $expr = Criteria::expr();
        $c->andWhere($expr->eq('name', 'ROLE_ORG_ADMIN'));
        $this->admin = $this->roles->matching($c)->count() > 0;
        return $this->admin;
    }

    /**
     * @return bool
     * @Groups("read_member")
     */
    public function isMessageAdmin(): bool
    {
        return !empty($this->getMessageAdmin());
    }

    public function getMessageAdmin(): ?bool
    {
        $c = Criteria::create();
        $expr = Criteria::expr();
        $c->andWhere($expr->orX(
            $expr->eq('name', 'ROLE_MSG_ADMIN'),
            $expr->eq('name', 'ROLE_ORG_ADMIN')
        ));
        $this->messageAdmin = $this->roles->matching($c)->count() > 0;
        return $this->messageAdmin;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateFulltextString()
    {
        if (empty($person = $this->person)) {
            $this->fulltextString = '';
        } else {
            $fulltextString = '';
            $fulltextString .= 'name: '.$person->getGivenName().' email: '.$person->getEmail().' employer: '.$person->getEmployerName().' job: '.$person->getJobTitle();
            $this->fulltextString = $fulltextString;
        }
    }

    /**
     * @ORM\PrePersist
     */
    public function initiateAccessToken()
    {
        if (empty($this->accessToken)) {
            $this->accessToken = AppUtil::generateUuid(sprintf(AppUtil::APP_NAME.'_IMT'));
        }
    }

    /**
     * @var string|null
     * @ORM\Column(type="string", length=191, nullable=true)
     * @Groups({"read_member", "write"})
     */
    private $groupName;

    /**
     * @var string
     * @Groups({"read_member", "write"})
     */
    private $organisationUuid;

    /**
     * @var string
     * @Groups("write")
     */
    private $personUuid;

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
     * @return array
     * @Groups({"read_member", "read"})
     */
    public function getPersonData()
    {
        $person = $this->person;
        if (empty($person)) {
            return [];
        }
        return [
            'uuid' => $person->getUuid(),
            'givenName' => $person->getGivenName(),
            'familyName' => $person->getFamilyName(),
            'gender' => $person->getGender(),

            'name' => $person->getGivenName().' '.$person->getFamilyName(),
            'jobTitle' => $person->getJobTitle(),
            'employerName' => $person->getEmployerName(),
            'dob' => $person->getBirthDate(),
            'nric' => ($nat = $person->getNationality()) ? $nat->getNricNumber() : '',
            'email' => $person->getEmail(),
            'phone' => $person->getPhoneNumber()
        ];
    }

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Messaging\NotifSubscription", mappedBy="individualMember")
     */
    private $notifSubscriptions;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Messaging\Delivery", mappedBy="recipient")
     */
    private $deliveries;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Event\Registration", mappedBy="individualMember")
     */
    private $registrations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Organisation\Connection", mappedBy="fromMember")
     * @ApiSubresource()
     */
    private $fromConnections;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Organisation\Connection", mappedBy="toMember")
     * @ApiSubresource()
     */
    private $toConnections;

    /**
     * @var string
     * @ORM\Column(type="string", length=191)
     * @Groups({"read_member"})
     */
    private $uuid;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     * @Groups({"read_member"})
     */
    private $createdAt;

    /**
     * @var Person
     * @ORM\ManyToOne(targetEntity="App\Entity\Person\Person", inversedBy="individualMembers", cascade={"persist","merge"})
     * @ORM\JoinColumn(name="id_person", referencedColumnName="id", onDelete="SET NULL")
     * @Groups("read")
     */
    private $person;

    /**
     * @var Organisation
     * @ORM\ManyToOne(targetEntity="App\Entity\Organisation\Organisation", inversedBy="individualMembers", cascade={"persist","merge"})
     * @ORM\JoinColumn(name="id_organisation", referencedColumnName="id", onDelete="SET NULL")
     */
    private $organisation;

    /**
     * @var string
     * @ORM\Column(type="string", length=191)
     */
    private $accessToken;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fulltextString;

    /**
     * @var boolean|null
     * @Groups("write")
     */
    public $admin = false;

    /**
     * @var boolean|null
     * @Groups("write")
     */
    public $messageDeliverable = false;

    /**
     * @var boolean|null
     * @Groups("write")
     */
    public $messageAdmin = false;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Organisation\Role", inversedBy="individualMembers")
     * @ORM\JoinTable(name="organisation__individuals_roles",
     *      joinColumns={@ORM\JoinColumn(name="id_individual", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="id_role", referencedColumnName="id")}
     *      )
     */
    private $roles;

    public function __construct()
    {
        $this->fromConnections = new ArrayCollection();
        $this->toConnections = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->roles = new ArrayCollection();
        $this->deliveries = new ArrayCollection();
        $this->notifSubscriptions = new ArrayCollection();
    }

    /**
     * @return Collection|NotifSubscription[]
     */
    public function getNotifSubscriptions(): Collection
    {
        return $this->notifSubscriptions;
    }

    public function addNotifSubscription(NotifSubscription $notifSubscription): self
    {
        if (!$this->notifSubscriptions->contains($notifSubscription)) {
            $this->notifSubscriptions[] = $notifSubscription;
            $notifSubscription->setIndividualMember($this);
        }

        return $this;
    }

    public function removeNotifSubscription(NotifSubscription $notifSubscription): self
    {
        if ($this->notifSubscriptions->contains($notifSubscription)) {
            $this->notifSubscriptions->removeElement($notifSubscription);
            // set the owning side to null (unless already changed)
            if ($notifSubscription->getIndividualMember() === $this) {
                $notifSubscription->setIndividualMember(null);
            }
        }

        return $this;
    }

    public function isMessageDelivered(Message $message)
    {
        if (empty($this->getMessageDelivery($message))) {
            return false;
        }

        return true;
    }

    /**
     * @param Message $message
     * @return Delivery|mixed|null
     */
    public function getMessageDelivery(Message $message)
    {
        if (array_key_exists($message->getId(), $this->messageDeliveryCache)) {
            if ($this->messageDeliveryCache[$message->getId()]) {
                return $this->messageDeliveryCache[$message->getId()];
            }
        }
        $c = Criteria::create();
        $expr = Criteria::expr();

        $c->where($expr->eq('message', $message));
        $deliveries = $this->deliveries->matching($c);
        if ($deliveries->count() > 0) {
            return $this->messageDeliveryCache[$message->getId()] = $deliveries->first();
        }

        return null;
    }

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read_member"})
     */
    private $membershipNo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read_member"})
     */
    private $membershipType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read_member"})
     */
    private $membershipClass;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default":false})
     * @Groups({"read_member"})
     */
    private $messagingExclusion = false;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"read_member"})
     */
    private $startedOn;

    /**
     * @ORM\Column(type="boolean", options={"default":true})
     * @Groups({"read_member"})
     */
    private $enabled = true;


    /**
     * @return Collection|Delivery[]
     */
    public function getDeliveries(): Collection
    {
        return $this->deliveries;
    }

    public function addDelivery(Delivery $delivery): self
    {
        if (!$this->deliveries->contains($delivery)) {
            $this->deliveries[] = $delivery;
            $delivery->setRecipient($this);
        }

        return $this;
    }

    public function removeDelivery(Delivery $delivery): self
    {
        if ($this->deliveries->contains($delivery)) {
            $this->deliveries->removeElement($delivery);
            // set the owning side to null (unless already changed)
            if ($delivery->getRecipient() === $this) {
                $delivery->setRecipient(null);
            }
        }

        return $this;
    }

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $messageAdminGranted;

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


    public function getMembershipNo(): ?string
    {
        return $this->membershipNo;
    }

    public function setMembershipNo(?string $membershipNo): self
    {
        $this->membershipNo = $membershipNo;

        return $this;
    }

    public function getMembershipType(): ?string
    {
        return $this->membershipType;
    }

    public function setMembershipType(?string $membershipType): self
    {
        $this->membershipType = $membershipType;

        return $this;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

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

    public function getOrganisation(): ?Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(?Organisation $organisation): self
    {
        $this->organisation = $organisation;

        return $this;
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

    /**
     * @return Collection|Connection[]
     */
    public function getFromConnections(): Collection
    {
        return $this->fromConnections;
    }

    public function addFromConnection(Connection $fromConnection): self
    {
        if (!$this->fromConnections->contains($fromConnection)) {
            $this->fromConnections[] = $fromConnection;
            $fromConnection->setFromMember($this);
        }

        return $this;
    }

    public function removeFromConnection(Connection $fromConnection): self
    {
        if ($this->fromConnections->contains($fromConnection)) {
            $this->fromConnections->removeElement($fromConnection);
            // set the owning side to null (unless already changed)
            if ($fromConnection->getFromMember() === $this) {
                $fromConnection->setFromMember(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Connection[]
     */
    public function getToConnections(): Collection
    {
        return $this->toConnections;
    }

    public function addToConnection(Connection $toConnection): self
    {
        if (!$this->toConnections->contains($toConnection)) {
            $this->toConnections[] = $toConnection;
            $toConnection->setToMember($this);
        }

        return $this;
    }

    public function removeToConnection(Connection $toConnection): self
    {
        if ($this->toConnections->contains($toConnection)) {
            $this->toConnections->removeElement($toConnection);
            // set the owning side to null (unless already changed)
            if ($toConnection->getToMember() === $this) {
                $toConnection->setToMember(null);
            }
        }

        return $this;
    }

    public function getFulltextString(): ?string
    {
        return $this->fulltextString;
    }

    public function setFulltextString(?string $fulltextString): self
    {
        $this->fulltextString = $fulltextString;

        return $this;
    }

    public function getPersonUuid(): ?string
    {
        return $this->personUuid;
    }

    public function setPersonUuid(?string $personUuid): self
    {
        $this->personUuid = $personUuid;

        return $this;
    }

    public function getOrganisationUuid(): ?string
    {
        if (empty($this->organisationUuid) && !empty($this->organisation)) {
            $this->organisationUuid = $this->organisation->getUuid();
        }
        return $this->organisationUuid;
    }

    public function setOrganisationUuid(?string $organisationUuid): self
    {
        $this->organisationUuid = $organisationUuid;

        return $this;
    }

    public function setAdmin(?bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function setMessageDeliverable(?bool $messageDeliverable): self
    {
        $this->messageDeliverable = $messageDeliverable;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailBody(): ?string
    {
        return $this->emailBody;
    }

    /**
     * @param string|null $emailBody
     */
    public function setEmailBody(?string $emailBody): void
    {
        $this->emailBody = $emailBody;
    }

    /**
     * @return string|null
     */
    public function getEmailSubject(): ?string
    {
        return $this->emailSubject;
    }

    /**
     * @param string|null $emailSubject
     */
    public function setEmailSubject(?string $emailSubject): void
    {
        $this->emailSubject = $emailSubject;
    }


    /**
     * @return Collection|Role[]
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole(Role $role): self
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
        }

        return $this;
    }

    public function hasRole(string $roleName): bool
    {
        $c = Criteria::create()->where(Criteria::expr()->eq('name', $roleName));
        return $this->roles->matching($c)->count() > 0;
    }

    public function getMembershipClass(): ?string
    {
        return $this->membershipClass;
    }

    public function setMembershipClass(?string $membershipClass): self
    {
        $this->membershipClass = $membershipClass;

        return $this;
    }

    public function getMessagingExclusion(): ?bool
    {
        return $this->messagingExclusion;
    }

    public function setMessagingExclusion(?bool $messagingExclusion): self
    {
        $this->messagingExclusion = $messagingExclusion;

        return $this;
    }

    public function getStartedOn(): ?\DateTimeInterface
    {
        return $this->startedOn;
    }

    public function setStartedOn(?\DateTimeInterface $startedOn): self
    {
        $this->startedOn = $startedOn;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    /**
     * @param string|null $groupName
     */
    public function setGroupName(?string $groupName): void
    {
        $this->groupName = $groupName;
    }

    public function getMessageAdminGranted(): ?bool
    {
        return $this->messageAdminGranted;
    }

    public function setMessageAdminGranted(?bool $messageAdminGranted): self
    {
        $this->messageAdminGranted = $messageAdminGranted;

        return $this;
    }
}
