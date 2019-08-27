<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use App\Util\AppUtil;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ApiResource(
 *     attributes={"access_control"="is_granted('ROLE_USER')"},
 *     normalizationContext={"groups"={"read_set_from_option", "read_option"}},
 *     denormalizationContext={"groups"={"write_set_from_option"}}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\MessageOptionRepository")
 * @ApiFilter(SearchFilter::class, properties={"uuid": "exact"})
 * @ORM\Table(name="messaging__option")
 * @ORM\HasLifecycleCallbacks()
 */
class MessageOption
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=191)
     * @Groups({"read", "read_option"})
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"read", "read_message", "read_option","write"})
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\OptionSet", inversedBy="messageOptions", cascade={"persist"})
     * @ORM\JoinColumn(name="id_option_set", referencedColumnName="id", onDelete="CASCADE")
     * @Groups({"read_set_from_option","write_set_from_option"})
     */
    private $optionSet;

    /**
     * @ORM\PrePersist
     */
    public function initiateUuid()
    {
        if (empty($this->uuid)) {
            $this->uuid = AppUtil::generateUuid();
        }
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
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
}
