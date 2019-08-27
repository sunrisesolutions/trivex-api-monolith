<?php

namespace App\Entity\Messaging;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;

use App\Filter\Messaging\NotLikeFilter;
use App\Filter\Messaging\GroupByFilter;

use App\Util\Messaging\AppUtil;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *     "access_control"="is_granted('ROLE_USER')",
 *     "order"={"id": "DESC"}
 * },
 *     normalizationContext={"groups"={"read", "read_message", "read_free_on"}},
 *     denormalizationContext={"groups"={"write"}}
 * )
 * @ApiFilter(DateFilter::class, properties={"readAt"})
 * @ApiFilter(ExistsFilter::class, properties={"readAt", "optionsSelectedAt", "selectedOptionsReadAt"})
 * @ApiFilter(SearchFilter::class, properties={"uuid": "exact", "message.sender.uuid": "exact", "message.type": "exact", "selectedOptions": "partial"})
 * @ApiFilter(BooleanFilter::class, properties={"selfDelivery"})
 * @ApiFilter(OrderFilter::class, properties={"recipient.person.name", "readAt"}, arguments={"orderParameterName"="order"})
 * @ApiFilter(NotLikeFilter::class)
 * @ApiFilter(GroupByFilter::class)
 *
 * @ORM\Entity(repositoryClass="App\Repository\Messaging\DeliveryRepository")
 * @ORM\Table(name="messaging__delivery")
 * @ORM\HasLifecycleCallbacks()
 */
class Delivery
{
    /**
     * @var int|null
     * @ORM\Id
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups("read")
     */
    private $id;

    public static function createInstance(Message $message, IndividualMember $recipient)
    {
        $d = new Delivery();
        $d->message = $message;
        $d->recipient = $recipient;

        return $d;
    }

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return integer|null
     * @Groups("read")
     */
    public function getMessageId()
    {
        return $this->message->getId();
    }

    /**
     * @return string|null
     * @Groups("read")
     */
    public function getRecipientUuid()
    {
        return $this->recipient->getUuid();
    }

    public function getUnreadDeliveryCount()
    {

    }

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid(AppUtil::APP_NAME.'_DELIV');
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function fixData()
    {
        if ($this->message->getSenderUuid() === $this->recipient->getUuid()) {
            $this->selfDelivery = true;
        } else {
            $this->selfDelivery = false;
        }
//        if (empty($this->optionsSelectedAt) && !empty($this->selectedOptions)) {
//            $this->optionsSelectedAt = new \DateTime();
//        }
    }

    /**
     * @ORM\Column(type="string", length=191)
     * @Groups("read")
     */
    private $uuid;

    /**
     * @ORM\Column(type="datetime")
     * @Groups("read")
     */
    private $createdAt;

    /**
     * @var boolean|null
     * @Groups("write")
     */
    private $read;


    /**
     * @var boolean|null
     * @Groups("write")
     */
    private $readSelectedOptions;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups("read")
     */
    private $readAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups("read")
     */
    private $updatedAt;

    /**
     * @var Message
     * @ORM\ManyToOne(targetEntity="App\Entity\Messaging\Message", inversedBy="deliveries")
     * @ORM\JoinColumn(name="id_message", referencedColumnName="id", onDelete="CASCADE")
     * @Groups("read")
     */
    private $message;

    /**
     * @var IndividualMember
     * @ORM\ManyToOne(targetEntity="App\Entity\Messaging\IndividualMember", inversedBy="deliveries")
     * @ORM\JoinColumn(name="id_recipient", referencedColumnName="id")
     * @Groups("read")
     */
    private $recipient;

    /**
     * @var array
     * @ORM\Column(type="magenta_json", nullable=true)
     * @Groups({"read", "write"})
     */
    private $selectedOptions = [];

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default":false})
     */
    private $selfDelivery;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups("read")
     */
    private $selectedOptionsReadAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups("read")
     */
    private $optionsSelectedAt;

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

    public function getReadAt(): ?\DateTimeInterface
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeInterface $readAt): self
    {
        $this->readAt = $readAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getRecipient(): ?IndividualMember
    {
        return $this->recipient;
    }

    public function setRecipient(?IndividualMember $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getSelectedOptions()
    {
        return $this->selectedOptions;
    }

    public function setSelectedOptions(array $selectedOptions): self
    {
        $this->selectedOptions = $selectedOptions;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getRead(): ?bool
    {
        return $this->read;
    }

    /**
     * @param bool|null $read
     */
    public function setRead(?bool $read): void
    {
        $this->read = $read;
    }

    /**
     * @return bool|null
     */
    public function getReadSelectedOptions(): ?bool
    {
        return $this->readSelectedOptions;
    }

    /**
     * @param bool|null $readSelectedOptions
     */
    public function setReadSelectedOptions(?bool $readSelectedOptions): void
    {
        $this->readSelectedOptions = $readSelectedOptions;
    }

    public function getSelfDelivery(): ?bool
    {
        return $this->selfDelivery;
    }

    public function setSelfDelivery(?bool $selfDelivery): self
    {
        $this->selfDelivery = $selfDelivery;

        return $this;
    }

    public function getSelectedOptionsReadAt(): ?\DateTimeInterface
    {
        return $this->selectedOptionsReadAt;
    }

    public function setSelectedOptionsReadAt(?\DateTimeInterface $selectedOptionsReadAt): self
    {
        $this->selectedOptionsReadAt = $selectedOptionsReadAt;

        return $this;
    }

    public function getOptionsSelectedAt(): ?\DateTimeInterface
    {
        return $this->optionsSelectedAt;
    }

    public function setOptionsSelectedAt(?\DateTimeInterface $optionsSelectedAt): self
    {
        $this->optionsSelectedAt = $optionsSelectedAt;

        return $this;
    }
}
