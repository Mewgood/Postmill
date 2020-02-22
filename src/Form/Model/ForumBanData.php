<?php

namespace App\Form\Model;

use App\Entity\Forum;
use App\Entity\ForumBan;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class ForumBanData {
    /**
     * @Assert\NotBlank(groups={"ban", "unban"})
     * @Assert\Length(max=300, groups={"ban", "unban"})
     *
     * @var string|null
     */
    private $reason;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expiryTime;

    public function toBan(Forum $forum, User $user, User $bannedBy): ForumBan {
        return new ForumBan($forum, $user, $this->reason, true, $bannedBy, $this->expiryTime);
    }

    public function toUnban(Forum $forum, User $user, User $bannedBy): ForumBan {
        return new ForumBan($forum, $user, $this->reason, false, $bannedBy);
    }

    public function getReason(): ?string {
        return $this->reason;
    }

    public function setReason(?string $reason): void {
        $this->reason = $reason;
    }

    public function getExpiryTime(): ?\DateTimeImmutable {
        return $this->expiryTime;
    }

    public function setExpiryTime(?\DateTimeInterface $expiryTime): void {
        if ($expiryTime instanceof \DateTime) {
            $expiryTime = \DateTimeImmutable::createFromMutable($expiryTime);
        }

        $this->expiryTime = $expiryTime;
    }
}
