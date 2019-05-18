<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ModeratorRepository")
 * @ORM\Table(name="moderators", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="moderator_forum_user_idx", columns={"forum_id", "user_id"})
 * })
 */
class Moderator {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var UuidInterface
     */
    private $id;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Forum", inversedBy="moderators")
     *
     * @var Forum
     */
    private $forum;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User", inversedBy="moderatorTokens")
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\Column(type="datetimetz")
     *
     * @var \DateTime
     */
    private $timestamp;

    public function __construct(Forum $forum, User $user) {
        $this->id = Uuid::uuid4();
        $this->forum = $forum;
        $this->user = $user;
        $this->timestamp = new \DateTime('@'.time());
        $forum->getModerators()->add($this);
        $user->getModeratorTokens()->add($this);
    }

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getForum(): Forum {
        return $this->forum;
    }

    public function getUser(): User {
        return $this->user;
    }

    public function getTimestamp(): \DateTime {
        return $this->timestamp;
    }

    public function userCanRemove($user): bool {
        if (!$user instanceof User) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // todo: allow other mods to remove in certain circumstances

        return $user === $this->user;
    }
}
