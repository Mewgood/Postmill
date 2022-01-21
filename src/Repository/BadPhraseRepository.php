<?php

namespace App\Repository;

use App\Entity\BadPhrase;
use App\PatternMatcher\PatternCollection;
use App\PatternMatcher\PatternCollectionInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\Collections\SelectableAdapter;
use Pagerfanta\Pagerfanta;

class BadPhraseRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, BadPhrase::class);
    }

    public function findPaginated(int $page): Pagerfanta {
        $criteria = Criteria::create()
            ->orderBy(['timestamp' => 'DESC', 'id' => 'ASC']);

        $adapter = new SelectableAdapter($this, $criteria);

        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(50);
        $pager->setCurrentPage($page);

        return $pager;
    }

    public function toPatternCollection(): PatternCollectionInterface {
        $criteria = Criteria::create()
            ->orderBy(['timestamp' => 'DESC', 'id' => 'ASC']);

        return new PatternCollection($this->matching($criteria)->toArray());
    }
}
