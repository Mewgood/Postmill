<?php

namespace App\CommentTree;

use App\Entity\Comment;

final class CommentTree {
    /**
     * @var CommentTree|null
     */
    private ?CommentTree $parent;

    /**
     * @var Comment
     */
    private Comment $comment;

    /**
     * @var bool
     */
    private bool $visible;

    public function __construct(
        ?self $parent,
        Comment $comment,
        bool $visible
    ) {
        $this->parent = $parent;
        $this->comment = $comment;
        $this->visible = $visible;
    }

    public function getParent(): ?self {
        return $this->parent;
    }

    public function getComment(): Comment {
        return $this->comment;
    }

    public function getVisible(): bool {
        return $this->visible;
    }

    public function setVisible(bool $visible): void {
        $this->visible = $visible;
    }
}
