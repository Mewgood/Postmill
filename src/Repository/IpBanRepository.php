<?php

namespace App\Repository;

use App\Entity\IpBan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\DoctrineSelectableAdapter;
use Pagerfanta\Pagerfanta;

class IpBanRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, IpBan::class);
    }

    /**
     * @return Pagerfanta|IpBan[]
     */
    public function findAllPaginated(int $page, $maxPerPage = 25): Pagerfanta {
        $criteria = Criteria::create()->orderBy(['timestamp' => 'DESC']);

        $bans = new Pagerfanta(new DoctrineSelectableAdapter($this, $criteria));
        $bans->setMaxPerPage($maxPerPage);
        $bans->setCurrentPage($page);

        return $bans;
    }

    public function ipIsBanned(string $ip): bool {
        $count = $this->_em->getConnection()->createQueryBuilder()
            ->select('COUNT(b)')
            ->from('bans', 'b')
            ->where('ip >>= :ip')
            ->andWhere('(expiry_date IS NULL OR expiry_date >= :now)')
            ->setParameter('ip', $ip, 'inet')
            ->setParameter('now', new \DateTime(), Type::DATETIMETZ)
            ->execute()
            ->fetchColumn();

        return $count > 0;
    }
}
