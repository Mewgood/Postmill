<?php

namespace App\Repository;

use App\Entity\Submission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SubmissionRepository extends ServiceEntityRepository {
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(
        ManagerRegistry $registry,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($registry, Submission::class);

        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Hydrate relations for increased performance.
     */
    public function hydrate(Submission ...$submissions): void {
        $this->_em->createQueryBuilder()
            ->select('PARTIAL s.{id}')
            ->addSelect('u')
            ->addSelect('f')
            ->from(Submission::class, 's')
            ->join('s.user', 'u')
            ->join('s.forum', 'f')
            ->where('s IN (?1)')
            ->setParameter(1, $submissions)
            ->getQuery()
            ->getResult();

        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            // hydrate submission votes for fast checking of user choice
            $this->_em->createQueryBuilder()
                ->select('PARTIAL s.{id}')
                ->addSelect('sv')
                ->from(Submission::class, 's')
                ->leftJoin('s.votes', 'sv')
                ->where('s IN (?1)')
                ->setParameter(1, $submissions)
                ->getQuery()
                ->getResult();
        }
    }

    public function findRemovableImages(array $images): array {
        if (!$images) {
            return [];
        }

        $nonRemovable = $this->createQueryBuilder('s')
            ->select('s.image')
            ->where('s.image IN (?1)')
            ->setParameter(1, $images)
            ->getQuery()
            ->execute();

        return array_diff($images, array_column($nonRemovable, 'image'));
    }
}
