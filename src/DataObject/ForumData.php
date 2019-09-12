<?php

namespace App\DataObject;

use App\Entity\Forum;
use App\Entity\ForumCategory;
use App\Entity\Theme;
use App\Entity\User;
use App\Serializer\Contracts\NormalizeMarkdownInterface;
use App\Validator\Constraints\Unique;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Unique("normalizedName", idFields={"id"}, groups={"create", "update"},
 *     entityClass="App\Entity\Forum", errorPath="name",
 *     message="A forum by that name already exists.")
 */
class ForumData implements NormalizeMarkdownInterface {
    /**
     * @var Forum|null
     */
    private $forum;

    /**
     * @Groups({"forum:read", "abbreviated_relations"})
     *
     * @var int|null
     */
    private $id;

    /**
     * @Assert\NotBlank(groups={"create", "update"})
     * @Assert\Length(min=3, max=25, groups={"create", "update"})
     * @Assert\Regex("/^\w+$/",
     *     message="The name must contain only contain letters, numbers, and underscores.",
     *     groups={"create", "update"}
     * )
     *
     * @Groups({"forum:read", "abbreviated_relations"})
     */
    private $name;

    /**
     * @var string|null
     */
    private $normalizedName;

    /**
     * @Assert\Length(max=100, groups={"create", "update"})
     * @Assert\NotBlank(groups={"create", "update"})
     *
     * @Groups({"forum:read"})
     *
     * @var string|null
     */
    private $title;

    /**
     * @Assert\Length(max=1500, groups={"create", "update"})
     * @Assert\NotBlank(groups={"create", "update"})
     *
     * @Groups({"forum:read"})
     *
     * @var string|null
     */
    private $sidebar;

    /**
     * @Assert\Length(max=300, groups={"create", "update"})
     * @Assert\NotBlank(groups={"create", "update"})
     *
     * @Groups({"forum:read"})
     *
     * @var string|null
     */
    private $description;

    /**
     * @Groups({"forum:read"})
     *
     * @var bool
     */
    private $featured = false;

    /**
     * @Groups({"forum:read"})
     *
     * @var ForumCategory|null
     */
    private $category;

    /**
     * @Groups({"forum:read"})
     *
     * @var Theme|null
     */
    private $suggestedTheme;

    public function __construct(Forum $forum = null) {
        if ($forum) {
            $this->forum = $forum;
            $this->id = $forum->getId();
            $this->setName($forum->getName());
            $this->title = $forum->getTitle();
            $this->sidebar = $forum->getSidebar();
            $this->description = $forum->getDescription();
            $this->featured = $forum->isFeatured();
            $this->category = $forum->getCategory();
            $this->suggestedTheme = $forum->getSuggestedTheme();
        }
    }

    public function toForum(User $user): Forum {
        $forum = new Forum(
            $this->name,
            $this->title,
            $this->description,
            $this->sidebar,
            $user
        );

        $forum->setFeatured($this->featured);
        $forum->setCategory($this->category);
        $forum->setSuggestedTheme($this->suggestedTheme);

        return $forum;
    }

    public function updateForum(Forum $forum): void {
        $forum->setName($this->name);
        $forum->setTitle($this->title);
        $forum->setSidebar($this->sidebar);
        $forum->setDescription($this->description);
        $forum->setFeatured($this->featured);
        $forum->setSuggestedTheme($this->suggestedTheme);
        $forum->setCategory($this->category);
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    /**
     * For unique validator.
     */
    public function getNormalizedName(): ?string {
        return $this->normalizedName;
    }

    public function setName(?string $name): void {
        $this->name = $name;
        $this->normalizedName = $name !== null ? Forum::normalizeName($name) : null;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(?string $title): void {
        $this->title = $title;
    }

    public function getSidebar(): ?string {
        return $this->sidebar;
    }

    public function setSidebar(?string $sidebar): void {
        $this->sidebar = $sidebar;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    public function isFeatured(): bool {
        return $this->featured;
    }

    public function setFeatured(bool $featured): void {
        $this->featured = $featured;
    }

    public function getSuggestedTheme(): ?Theme {
        return $this->suggestedTheme;
    }

    public function setSuggestedTheme(?Theme $suggestedTheme): void {
        $this->suggestedTheme = $suggestedTheme;
    }

    public function getCategory(): ?ForumCategory {
        return $this->category;
    }

    public function setCategory(?ForumCategory $category): void {
        $this->category = $category;
    }

    public function getMarkdownFields(): iterable {
        yield 'sidebar';
    }

    public function getMarkdownContext(): array {
        return [
            'context' => 'forum',
            'forum' => $this->forum,
        ];
    }
}
