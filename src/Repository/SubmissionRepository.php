<?php

namespace App\Repository;

use App\Entity\Submission;
use App\Repository\Contracts\PrunesIpAddresses;
use App\Repository\Traits\PrunesIpAddressesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

class SubmissionRepository extends ServiceEntityRepository implements PrunesIpAddresses {
    use PrunesIpAddressesTrait;

    /**
     * @var Security
     */
    private $security;

    public function __construct(ManagerRegistry $registry, Security $security) {
        parent::__construct($registry, Submission::class);

        $this->security = $security;
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

        if ($this->security->getToken() && $this->security->isGranted('ROLE_USER')) {
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
}
