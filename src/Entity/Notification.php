<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NotificationRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="notification_type", type="text")
 * @ORM\DiscriminatorMap({
 *     "comment": "CommentNotification",
 *     "comment_mention": "CommentMention",
 *     "message": "MessageNotification",
 *     "submission_mention": "SubmissionMention",
 * })
 */
abstract class Notification {
    /**
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     * @ORM\Id()
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(type="datetimetz_immutable", nullable=true)
     *
     * @var \DateTimeImmutable|null
     */
    private $notifyAt;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="User", inversedBy="notifications")
     *
     * @var User
     */
    private $user;

    public function __construct(User $receiver) {
        $this->user = $receiver;
        $this->notifyAt = new \DateTime('@'.time());
    }

    abstract public function getType(): string;

    public function getId(): ?int {
        return $this->id;
    }

    public function getNotifyAt(): ?\DateTimeImmutable {
        return $this->notifyAt;
    }

    public function setNotifyAt(?\DateTimeInterface $notifyAt): void {
        if ($notifyAt instanceof \DateTime) {
            $notifyAt = \DateTimeImmutable::createFromMutable($notifyAt);
        }

        $this->notifyAt = $notifyAt;
    }

    public function getUser(): User {
        return $this->user;
    }
}
