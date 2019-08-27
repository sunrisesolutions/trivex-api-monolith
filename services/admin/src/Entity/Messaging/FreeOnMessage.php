<?php

namespace App\Entity\Messaging;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Util\Messaging\AppUtil;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Parent_;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *     "access_control"="is_granted('ROLE_USER')",
 *     "order"={"id": "DESC"}
 * },
 *     normalizationContext={"groups"={"read_free_on", "read_message"}},
 *     denormalizationContext={"groups"={"write_free_on", "write_message"}}
 * )
 * @ApiFilter(DateFilter::class, properties={"readAt"})
 * @ApiFilter(ExistsFilter::class, properties={"readAt"})
 * @ORM\Entity(repositoryClass="App\Repository\Messaging\FreeOnMessageRepository")
 * @ORM\Table(name="messaging__message__free_on")
 * @ORM\HasLifecycleCallbacks()
 */
class FreeOnMessage extends Message
{
//    /**
//     * @var int|null
//     * @ORM\Id
//     * @ORM\Column(type="integer",options={"unsigned":true})
//     * @ORM\GeneratedValue(strategy="AUTO")
//     */
//    protected $id;

    const DAYS = [
        'SATURDAY',
        'SUNDAY',
        'MONDAY',
        'TUESDAY',
        'WEDNESDAY',
        'THURSDAY',
        'FRIDAY',
        'SATURDAY',
        'SUNDAY',
        'MONDAY',
        'TUESDAY',
        'WEDNESDAY',
        'THURSDAY',
        'FRIDAY',
        'SATURDAY',
        'SUNDAY',
    ];

    public function __construct()
    {
        $this->type = Message::TYPE_FREE_ON;
        parent::__construct();
    }

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid(AppUtil::APP_NAME.'_FREE_ON');
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function fixData()
    {
        parent::fixData();
        $this->subject = 'Free-on Messages from '.($name = $this->sender->getPerson()->getName());
        $this->body = sprintf('%s is free from %s:%s to %s:%s on%s starting from %s to %s.', $name, $this->fromHour, $this->fromMinute, $this->toHour, $this->toMinute, $this->getFreeDays(), $this->effectiveFrom ? $this->effectiveFrom->format('d-m-Y') : 'Unknown Date', $this->expireAt ? $this->expireAt->format('d-m-Y') : 'Unknown Date');
    }

    private function getFreeDays()
    {
        return ($this->freeOnMondays ? ' Mondays' : '').($this->freeOnTuesdays ? ' Tuesdays' : '').($this->freeOnWednesdays ? ' Wednesdays' : '').($this->freeOnThursdays ? ' Thursdays' : '').($this->freeOnFridays ? ' Fridays' : '').($this->freeOnSaturdays ? ' Saturdays' : '').($this->freeOnSundays ? ' Sundays' : '');
    }

    /**
     * @ORM\Column(type="string", length=191)
     * @Groups("read_free_on")
     */
    protected $uuid;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"read_free_on","write_free_on"})
     */
    protected $fromHour;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"read_free_on","write_free_on"})
     */
    protected $toHour;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"read_free_on","write_free_on"})
     */
    protected $fromMinute;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"read_free_on","write_free_on"})
     */
    protected $toMinute;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"read_free_on","write_free_on"})
     */
    protected $freeOnMondays;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"read_free_on","write_free_on"})
     */
    protected $freeOnTuesdays;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"read_free_on","write_free_on"})
     */
    protected $freeOnWednesdays;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"read_free_on","write_free_on"})
     */
    protected $freeOnThursdays;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"read_free_on","write_free_on"})
     */
    protected $freeOnFridays;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"read_free_on","write_free_on"})
     */
    protected $freeOnSaturdays;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"read_free_on","write_free_on"})
     */
    protected $freeOnSundays;

    public function getFromHour(): ?int
    {
        return $this->fromHour;
    }

    public function setFromHour(?int $fromHour): self
    {
        $this->fromHour = $fromHour;

        return $this;
    }

    public function getToHour(): ?int
    {
        return $this->toHour;
    }

    public function setToHour(?int $toHour): self
    {
        $this->toHour = $toHour;

        return $this;
    }

    public function getFromMinute(): ?int
    {
        return $this->fromMinute;
    }

    public function setFromMinute(?int $fromMinute): self
    {
        $this->fromMinute = $fromMinute;

        return $this;
    }

    public function getToMinute(): ?int
    {
        return $this->toMinute;
    }

    public function setToMinute(?int $toMinute): self
    {
        $this->toMinute = $toMinute;

        return $this;
    }

    public function getFreeOnMondays(): ?bool
    {
        return $this->freeOnMondays;
    }

    public function setFreeOnMondays(?bool $freeOnMondays): self
    {
        $this->freeOnMondays = $freeOnMondays;

        return $this;
    }

    public function getFreeOnTuesdays(): ?bool
    {
        return $this->freeOnTuesdays;
    }

    public function setFreeOnTuesdays(?bool $freeOnTuesdays): self
    {
        $this->freeOnTuesdays = $freeOnTuesdays;

        return $this;
    }

    public function getFreeOnWednesdays(): ?bool
    {
        return $this->freeOnWednesdays;
    }

    public function setFreeOnWednesdays(?bool $freeOnWednesdays): self
    {
        $this->freeOnWednesdays = $freeOnWednesdays;

        return $this;
    }

    public function getFreeOnThursdays(): ?bool
    {
        return $this->freeOnThursdays;
    }

    public function setFreeOnThursdays(?bool $freeOnThursdays): self
    {
        $this->freeOnThursdays = $freeOnThursdays;

        return $this;
    }

    public function getFreeOnFridays(): ?bool
    {
        return $this->freeOnFridays;
    }

    public function setFreeOnFridays(?bool $freeOnFridays): self
    {
        $this->freeOnFridays = $freeOnFridays;

        return $this;
    }

    public function getFreeOnSaturdays(): ?bool
    {
        return $this->freeOnSaturdays;
    }

    public function setFreeOnSaturdays(?bool $freeOnSaturdays): self
    {
        $this->freeOnSaturdays = $freeOnSaturdays;

        return $this;
    }

    public function getFreeOnSundays(): ?bool
    {
        return $this->freeOnSundays;
    }

    public function setFreeOnSundays(?bool $freeOnSundays): self
    {
        $this->freeOnSundays = $freeOnSundays;

        return $this;
    }
}
