<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @return int numbers of rows cleared
     */
    public function clearNotifications(User $user, int $max = null): int {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->delete(Notification::class, 'n')
            ->where('n.user = ?1')
            ->setParameter(1, $user);

        if ($max) {
            $qb->andWhere('n.id <= ?2')->setParameter(2, $max);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * @return int numbers of rows cleared
     */
    public function clearNotification(User $user, int $notificationId): int {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->delete(Notification::class, 'n')
            ->where('n.user = ?1')
            ->setParameter(1, $user)
            ->andWhere('n.id = ?2')
            ->setParameter(2, $notificationId);

        return $qb->getQuery()->execute();
    }
}
