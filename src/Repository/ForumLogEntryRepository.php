<?php

namespace App\Repository;

use App\Entity\Forum;
use App\Entity\ForumLogEntry;
use App\Entity\Moderator;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\Collections\SelectableAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

class ForumLogEntryRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, ForumLogEntry::class);
    }

    /**
     * @return Pagerfanta<ForumLogEntry>
     */
    public function findAllPaginatedPrivileged(int $page, User $user, int $maxPerPage = 50): Pagerfanta {
        if (!$user->isAdmin()) {
            $dql = 'SELECT fle FROM '.ForumLogEntry::class.' fle WHERE '.
            'fle.forum IN (SELECT IDENTITY(m.forum) FROM '.Moderator::class.' m WHERE m.user = ?1) '.
            'OR fle.forum IN (SELECT f FROM '.Forum::class.' f where f.moderationLogPublic = TRUE) '.
            'ORDER BY fle.timestamp DESC';

            $query = $this->getEntityManager()->createQuery($dql)->setParameter(1, $user);
            $adapter = new QueryAdapter($query);
        } else {
            $criteria = Criteria::create()->orderBy(['timestamp' => 'DESC']);
            $adapter = new SelectableAdapter($this, $criteria);
        }

        /** @psalm-suppress InvalidArgument */
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        return $pager;
    }

    /**
     * @return Pagerfanta<ForumLogEntry>
     */
    public function findAllPaginated(int $page, int $maxPerPage = 50): Pagerfanta {
        $dql = 'SELECT fle FROM '.ForumLogEntry::class.' fle WHERE '.
            'fle.forum IN (SELECT f FROM '.Forum::class.' f where f.moderationLogPublic = TRUE) '.
            'ORDER BY fle.timestamp DESC';

        $query = $this->getEntityManager()->createQuery($dql);

        $pager = new Pagerfanta(new QueryAdapter($query));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        return $pager;
    }
}
