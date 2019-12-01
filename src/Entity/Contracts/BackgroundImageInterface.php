<?php

namespace App\Entity\Contracts;

interface BackgroundImageInterface {
    public const BACKGROUND_TILE = 'tile';
    public const BACKGROUND_CENTER = 'center';
    public const BACKGROUND_FIT_TO_PAGE = 'fit_to_page';

    public function getLightBackgroundImage(): ?string;

    public function setLightBackgroundImage(?string $lightBackgroundImage): void;

    public function getDarkBackgroundImage(): ?string;

    public function setDarkBackgroundImage(?string $darkBackgroundImage): void;

    public function getBackgroundImageMode(): string;

    public function setBackgroundImageMode(string $backgroundImageMode): void;
}
