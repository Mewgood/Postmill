<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * In the future, we might use this for multi-site support. But for now, this is
 * a single-row table where some global settings are stored.
 *
 * @ORM\Entity(repositoryClass="App\Repository\SiteRepository")
 */
class Site {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var UuidInterface
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $siteName;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $wikiEnabled = true;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $forumCreateRole = 'ROLE_USER';

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $imageUploadRole = 'ROLE_USER';

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $wikiEditRole = 'ROLE_USER';

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getSiteName(): string {
        return $this->siteName;
    }

    public function setSiteName(string $siteName): void {
        $this->siteName = $siteName;
    }

    public function isWikiEnabled(): bool {
        return $this->wikiEnabled;
    }

    public function setWikiEnabled(bool $wikiEnabled): void {
        $this->wikiEnabled = $wikiEnabled;
    }

    public function getForumCreateRole(): string {
        return $this->forumCreateRole;
    }

    public function setForumCreateRole(string $forumCreateRole): void {
        if (!\in_array($forumCreateRole, User::ROLES, true)) {
            throw new \InvalidArgumentException("Invalid role '$forumCreateRole'");
        }

        $this->forumCreateRole = $forumCreateRole;
    }

    public function getImageUploadRole(): string {
        return $this->imageUploadRole;
    }

    public function setImageUploadRole(string $imageUploadRole): void {
        if (!\in_array($imageUploadRole, User::ROLES, true)) {
            throw new \InvalidArgumentException("Invalid role '$imageUploadRole'");
        }

        $this->imageUploadRole = $imageUploadRole;
    }

    public function getWikiEditRole(): string {
        return $this->wikiEditRole;
    }

    public function setWikiEditRole(string $wikiEditRole): void {
        if (!\in_array($wikiEditRole, User::ROLES, true)) {
            throw new \InvalidArgumentException("Invalid role '$wikiEditRole'");
        }

        $this->wikiEditRole = $wikiEditRole;
    }
}
