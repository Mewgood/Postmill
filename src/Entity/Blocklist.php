<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BlocklistRepository")
 */
class Blocklist {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var UuidInterface
     */
    private $id;

    /**
     * @ORM\Column(type="text", unique=true)
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="text", unique=true)
     *
     * @var string
     */
    private $url;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $regex;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $ttl;

    public function __construct(string $name, string $url, string $regex, int $ttl) {
        $this->id = Uuid::uuid4();
        $this->name = $name;
        $this->url = $url;
        $this->regex = $regex;
        $this->ttl = $ttl;
    }

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getUrl(): string {
        return $this->url;
    }

    public function getRegex(): string {
        return $this->regex;
    }

    public function getTtl(): int {
        return $this->ttl;
    }
}
