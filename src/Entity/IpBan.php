<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\IpBanRepository")
 * @ORM\Table(name="bans")
 */
class IpBan {
    /**
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue()
     * @ORM\Id()
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(type="inet")
     *
     * @var string
     */
    private $ip;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string
     */
    private $reason;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="ipBans")
     *
     * @var User|null
     */
    private $user;

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
     * @var \DateTimeImmutable
     */
    private $expiryDate;

    public function __construct(
        string $ip,
        string $reason,
        ?User $user,
        User $bannedBy,
        \DateTimeInterface $expiryDate = null
    ) {
        if ($expiryDate instanceof \DateTime) {
            $expiryDate = \DateTimeImmutable::createFromMutable($expiryDate);
        }

        $this->ip = $ip;
        $this->reason = $reason;
        $this->user = $user;
        $this->bannedBy = $bannedBy;
        $this->expiryDate = $expiryDate;
        $this->timestamp = new \DateTimeImmutable('@'.time());
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getIp(): string {
        return $this->ip;
    }

    public function getReason(): string {
        return $this->reason;
    }

    public function getUser(): ?User {
        return $this->user;
    }

    public function getBannedBy(): User {
        return $this->bannedBy;
    }

    public function getTimestamp(): \DateTimeImmutable {
        return $this->timestamp;
    }

    public function getExpiryDate(): ?\DateTimeImmutable {
        return $this->expiryDate;
    }
}
