<?php

namespace App\Entity;

use App\Entity\Contracts\VotableInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base class for all vote entities.
 *
 * @ORM\MappedSuperclass()
 */
abstract class Vote {
    /**
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id()
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $upvote;

    /**
     * @ORM\Column(type="datetimetz")
     *
     * @var \DateTime
     */
    private $timestamp;

    /**
     * @ORM\JoinColumn(name="user_id", nullable=false)
     * @ORM\ManyToOne(targetEntity="User")
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\Column(type="inet", nullable=true)
     *
     * @var string|null
     */
    private $ip;

    /**
     * @param int $choice one of VotableInterface::VOTE_UP or
     *                    VotableInterface::VOTE_DOWN
     *
     * @throws \InvalidArgumentException if $choice is bad
     * @throws \InvalidArgumentException if IP address isn't valid
     */
    public function __construct(int $choice, User $user, ?string $ip) {
        if ($choice === VotableInterface::VOTE_NONE) {
            throw new \InvalidArgumentException('A vote entity cannot have a "none" status');
        }

        if ($choice !== VotableInterface::VOTE_UP && $choice !== VotableInterface::VOTE_DOWN) {
            throw new \InvalidArgumentException('Unknown choice');
        }

        if ($ip !== null && !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Bad IP address');
        }

        $this->upvote = $choice === VotableInterface::VOTE_UP;
        $this->user = $user;
        $this->ip = $user->isWhitelistedOrAdmin() ? null : $ip;
        $this->timestamp = new \DateTime('@'.time());
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getChoice(): int {
        return $this->upvote
            ? VotableInterface::VOTE_UP
            : VotableInterface::VOTE_DOWN;
    }

    public function getUser(): User {
        return $this->user;
    }

    public function getIp(): ?string {
        return $this->ip;
    }

    public function getTimestamp(): \DateTime {
        return $this->timestamp;
    }

    /**
     * Legacy getter needed for `Selectable` compatibility.
     *
     * @internal
     */
    public function getUpvote(): bool {
        return $this->upvote;
    }
}
