<?php

namespace App\DataObject;

use App\Entity\Blocklist;
use App\Validator\RegularExpression;
use App\Validator\Unique;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Unique("name", entityClass="App\Entity\Blocklist", idFields={"id"})
 */
class BlocklistData {
    /**
     * @var UuidInterface|null
     */
    private $id;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=100)
     *
     * @var string|null
     */
    private $name;

    /**
     * @Assert\NotBlank()
     * @Assert\Url()
     *
     * @var string|null
     */
    private $url;

    /**
     * @Assert\NotBlank()
     * @RegularExpression()
     *
     * @var string
     */
    private $regex;

    /**
     * @Assert\NotBlank()
     * @Assert\LessThanOrEqual(4294967295)
     * @Assert\Positive()
     *
     * @var int|null
     */
    private $ttl;

    public static function createFromBlocklist(Blocklist $blocklist): self {
        $self = new self();
        $self->id = $blocklist->getId();
        $self->name = $blocklist->getName();
        $self->url = $blocklist->getUrl();
        $self->regex = $blocklist->getRegex();
        $self->ttl = $blocklist->getTtl();

        return $self;
    }

    public function toBlocklist(): Blocklist {
        return new Blocklist($this->name, $this->url, $this->regex, $this->ttl);
    }

    public function getId(): ?UuidInterface {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(?string $name): void {
        $this->name = $name;
    }

    public function getUrl(): ?string {
        return $this->url;
    }

    public function setUrl(?string $url): void {
        $this->url = $url;
    }

    public function getRegex(): ?string {
        return $this->regex;
    }

    public function setRegex(?string $regex): void {
        $this->regex = $regex;
    }

    public function getTtl(): ?int {
        return $this->ttl;
    }

    public function setTtl(?int $ttl): void {
        $this->ttl = $ttl;
    }
}
