<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;

use App\Filter\NotLikeFilter;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Util\AppUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Self_;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use App\Controller\MessageApprovalController;

/**
 * @ApiResource(
 *     attributes={
 *     "access_control"="is_granted('ROLE_USER')",
 *     "order"={"id": "DESC"}
 * },
 *     normalizationContext={"groups"={"read_message"}},
 *     denormalizationContext={"groups"={"write_message"}},
 *     itemOperations={
 *      "get",
 *      "post_message_approval"={
 *          "method"="POST",
 *          "path"="/messages/{id}/approval",
 *          "controller"=MessageApprovalController::class,
 *          "access_control"="is_granted('ROLE_ORG_ADMIN')",
 *          "normalization_context"={"groups"={"post_message_approval"}},
 *          "denormalization_context"={"groups"={"post_message_approval"}},
 *      },
 *      "put",
 *      "delete",
 *     }
 * )
 * @ApiFilter(SearchFilter::class, properties={"uuid": "exact", "sender.uuid": "exact", "optionSet.uuid": "uuid", "status":"exact", "type":"exact"})
 * @ApiFilter(BooleanFilter::class, properties={"senderMessageAdmin"})
 * @ApiFilter(NotLikeFilter::class)
 * @ApiFilter(ExistsFilter::class, properties={"approvalDecidedAt", "approvalDecisionReadAt"})
 *
 * @ORM\Entity(repositoryClass="App\Repository\MessageRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"free_on" = "App\Entity\FreeOnMessage", "simple" = "App\Entity\Message"})
 * @ORM\Table(name="messaging__message")
 * @ORM\HasLifecycleCallbacks()
 */
class Message
{
    const TYPE_SIMPLE = 'SIMPLE';
    const TYPE_FREE_ON = 'FREE_ON';

    const STATUS_DRAFT = 'MESSAGE_DRAFT';
    const STATUS_NEW = 'MESSAGE_NEW';
    const STATUS_PENDING_APPROVAL = 'MESSAGE_PENDING_APPROVAL';
    const STATUS_DELIVERY_REJECTED = 'DELIVERY_REJECTED';
    const STATUS_DELIVERY_IN_PROGRESS = 'DELIVERY_IN_PROGRESS';
    const STATUS_DELIVERY_SUCCESSFUL = 'DELIVERY_SUCCESSFUL';
    const STATUS_RECEIVED = 'MESSAGE_RECEIVED';

    const STATUS_READ = 'MESSAGE_READ';

    /**
     * @var int|null
     * @ORM\Id
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups("read_message")
     */
    protected $id;

    /**
     * @var boolean $approved
     * @Groups("write_message")
     */
    protected $approved;

    /**
     * @var boolean $rejected
     * @Groups("write_message")
     */
    protected $rejected;

    /**
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->approved;
    }

    /**
     * @param bool $approved
     */
    public function setApproved(bool $approved): void
    {
        $this->approved = $approved;
        if ($approved && empty($this->approvalDecidedAt)) {
            $this->status = self::STATUS_NEW;
            $this->approvalDecidedAt = new \DateTime();
        }
    }

    /**
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->rejected;
    }

    /**
     * @param bool $rejected
     */
    public function setRejected(bool $rejected): void
    {
        $this->rejected = $rejected;
        if ($rejected && empty($this->approvalDecidedAt)) {
            $this->status = self::STATUS_DELIVERY_REJECTED;
            $this->approvalDecidedAt = new \DateTime();
        }
    }

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = self::STATUS_DRAFT;
        $this->deliveries = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function fixData()
    {
        if (empty($this->effectiveFrom)) {
            $this->effectiveFrom = $this->createdAt;
        }
        if (empty($this->expireAt)) {
            $this->expireAt = new \DateTime();
            $this->expireAt->modify('+30 days');
        }
        if ($this->senderMessageAdmin === null) {
            $this->senderMessageAdmin = $this->sender->isMessageAdmin();
        }
        if (empty($this->approvalDecidedAt) && $this->status === self::STATUS_DELIVERY_SUCCESSFUL) {
            $this->approvalDecidedAt = $this->createdAt;
        }
    }

    public function getDecisionStatus(): string
    {
        return $this->status;
    }

    public function getRecipientsByPage(): ?Collection
    {
        if (empty($this->conversation)) {
            $members = $this->organisation->getIndividualMembersByPage();
            if (empty($members)) {
                return null;
            }
        } else {
//            throw new UnsupportedException('Not yet implemented');
            $members = $this->conversation->getParticipants();
//                $this->status = self::STATUS_DELIVERY_SUCCESSFUL;
        }
        return $members;
    }

    public function commitDeliveries()
    {
        $message = $this;

        $deliveries = [];
        if (in_array($this->status, [self::STATUS_NEW, self::STATUS_DELIVERY_IN_PROGRESS])) {
            if (empty($members = $this->getRecipientsByPage())) {
                return false;
            }
            /** @var IndividualMember $member */
            foreach ($members as $member) {
                if ($member->isMessageDelivered($message)) { // || $member->getUuid() === $message->getSender()->getUuid() // can the sender receives his own messages
                    continue;
                }

                $recipient = $member;
                $delivery = Delivery::createInstance($this, $recipient);
                $deliveries[] = $delivery;
            }
        } else {
            return false;
        }

        return $deliveries;
    }

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid();
            if (empty($this->code)) {
                $this->code = $this->uuid;
            }
        }
    }

    /**
     * @Groups("write_message")
     */
    protected $published;

    public function setPublished(?bool $published): self
    {
        $this->published = $published;
        if ($published && $this->status === self::STATUS_DRAFT) {
            $this->setStatus(self::STATUS_NEW);
        }

        return $this;
    }

    /**
     * @ORM\Column(type="string", length=191)
     * @Groups("read_message")
     */
    protected $uuid;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("read_message")
     */
    protected $createdAt;

    /**
     * @var Conversation
     * @ORM\ManyToOne(targetEntity="App\Entity\Conversation", inversedBy="messages", cascade={"persist", "merge"})
     * @ORM\JoinColumn(name="id_conversation", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $conversation;

    /**
     * @var Organisation
     * @ORM\ManyToOne(targetEntity="App\Entity\Organisation", inversedBy="messages")
     * @ORM\JoinColumn(name="id_organisation", referencedColumnName="id")
     */
    protected $organisation;

    /**
     * @var IndividualMember
     * @ORM\ManyToOne(targetEntity="App\Entity\IndividualMember", inversedBy="messages")
     * @ORM\JoinColumn(name="id_sender", referencedColumnName="id")
     */
    protected $sender;

    /**
     * @return string
     * @Groups("read_message")
     */
    public function getSenderUuid()
    {
        return $this->sender->getUuid();
    }

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read_message", "write_message"})
     */
    protected $subject;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"read_message", "write_message"})
     */
    protected $body;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups("read_message")
     */
    protected $status;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Delivery", mappedBy="message")
     * @ApiSubresource()
     */
    protected $deliveries;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\OptionSet", inversedBy="messages")
     * @Groups({"read_message", "write_message"})
     */
    protected $optionSet;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"read_message", "write_message"})
     */
    protected $expireAt;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"read_message", "write_message"})
     */
    protected $expireIn;
    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"read_message", "write_message"})
     */
    protected $expireInUnit;
    /**
     * @ORM\Column(type="string", length=255, nullable=true, options={"default":"SIMPLE"})
     */
    protected $type = self::TYPE_SIMPLE;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"read_message", "write_message"})
     */
    protected $effectiveFrom;

    /**
     * @ORM\Column(type="string", length=128, options={"default": "Asia/Singapore"})
     */
    protected $timezone = 'Asia/Singapore';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"read_message", "write_message"})
     */
    private $decisionReasons;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"read_message"})
     */
    private $senderMessageAdmin;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $approvalDecidedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $approvalDecisionReadAt;

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

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): self
    {
        $this->conversation = $conversation;

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

    public function getSender(): ?IndividualMember
    {
        return $this->sender;
    }

    public function setSender(?IndividualMember $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

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
            $delivery->setMessage($this);
        }

        return $this;
    }

    public function removeDelivery(Delivery $delivery): self
    {
        if ($this->deliveries->contains($delivery)) {
            $this->deliveries->removeElement($delivery);
            // set the owning side to null (unless already changed)
            if ($delivery->getMessage() === $this) {
                $delivery->setMessage(null);
            }
        }

        return $this;
    }

    public function getPublished(): ?bool
    {
        return $this->published;
    }

    public function getOptionSet(): ?OptionSet
    {
        return $this->optionSet;
    }

    public function setOptionSet(?OptionSet $optionSet): self
    {
        $this->optionSet = $optionSet;

        return $this;
    }

    public function getExpireAt(): ?\DateTimeInterface
    {
        return $this->expireAt;
    }

    public function setExpireAt(?\DateTimeInterface $expireAt): self
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    public function getExpireIn(): ?int
    {
        return $this->expireIn;
    }

    public function setExpireIn(?int $expireIn): self
    {
        $this->expireIn = $expireIn;

        return $this;
    }

    public function getExpireInUnit(): ?string
    {
        return $this->expireInUnit;
    }

    public function setExpireInUnit(?string $expireInUnit): self
    {
        $this->expireInUnit = $expireInUnit;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEffectiveFrom(): ?\DateTimeInterface
    {
        return $this->effectiveFrom;
    }

    public function setEffectiveFrom(?\DateTimeInterface $effectiveFrom): self
    {
        $this->effectiveFrom = $effectiveFrom;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getDecisionReasons(): ?string
    {
        return $this->decisionReasons;
    }

    public function setDecisionReasons(?string $decisionReasons): self
    {
        $this->decisionReasons = $decisionReasons;

        return $this;
    }

    public function getSenderMessageAdmin(): ?bool
    {
        return $this->senderMessageAdmin;
    }

    public function setSenderMessageAdmin(?bool $senderMessageAdmin): self
    {
        $this->senderMessageAdmin = $senderMessageAdmin;

        return $this;
    }

    public function getApprovalDecidedAt(): ?\DateTimeInterface
    {
        return $this->approvalDecidedAt;
    }

    public function setApprovalDecidedAt(?\DateTimeInterface $approvalDecidedAt): self
    {
        $this->approvalDecidedAt = $approvalDecidedAt;

        return $this;
    }

    public function getApprovalDecisionReadAt(): ?\DateTimeInterface
    {
        return $this->approvalDecisionReadAt;
    }

    public function setApprovalDecisionReadAt(?\DateTimeInterface $approvalDecisionReadAt): self
    {
        $this->approvalDecisionReadAt = $approvalDecisionReadAt;

        return $this;
    }

}
