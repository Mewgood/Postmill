<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserBanRepository")
 */
class UserBan {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var Uuid
     */
    private $id;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User", inversedBy="bans")
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $reason;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $banned;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User
     */
    private $bannedBy;

    /**
     * @ORM\Column(type="datetimetz_immutable")
     *
     * @var \DateTimeImmutable
     */
    private $timestamp;

    /**
     * @ORM\Column(type="datetimetz_immutable", nullable=true)
     *
     * @var \DateTimeImmutable|null
     */
    private $expiresAt;

    public function __construct(
        User $user,
        string $reason,
        bool $banned,
        User $bannedBy,
        \DateTimeInterface $expiresAt = null
    ) {
        if (!$banned && $expiresAt) {
            throw new \DomainException('Unbans cannot have expiry times');
        }

        if ($expiresAt instanceof \DateTime) {
            $expiresAt = \DateTimeImmutable::createFromMutable($expiresAt);
        }

        $this->id = Uuid::uuid4();
        $this->user = $user;
        $this->reason = $reason;
        $this->banned = $banned;
        $this->bannedBy = $bannedBy;
        $this->expiresAt = $expiresAt;
        $this->timestamp = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6f', microtime(true)));
    }

    public function getId(): Uuid {
        return $this->id;
    }

    public function getUser(): User {
        return $this->user;
    }

    public function getReason(): string {
        return $this->reason;
    }

    public function isBan(): bool {
        return $this->banned;
    }

    public function getBannedBy(): User {
        return $this->bannedBy;
    }

    public function getTimestamp(): \DateTimeImmutable {
        return $this->timestamp;
    }

    public function getExpiresAt(): ?\DateTimeImmutable {
        return $this->expiresAt;
    }

    public function isExpired(): bool {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt->getTimestamp() < time();
    }
}
