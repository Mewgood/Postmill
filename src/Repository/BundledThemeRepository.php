<?php

namespace App\Repository;

use App\Entity\BundledTheme;
use Doctrine\Persistence\ManagerRegistry;

class BundledThemeRepository extends ThemeRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, BundledTheme::class);
    }
}
