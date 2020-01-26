<?php

namespace App\Entity\Contracts;

interface VisibilityInterface {
    public const VISIBILITY_VISIBLE = 'visible';
    public const VISIBILITY_DELETED = 'deleted';
    // TODO: queued for moderation

    /**
     * @return string one of VISIBILITY_* constants
     */
    public function getVisibility(): string;

    public function softDelete(): void;
}
