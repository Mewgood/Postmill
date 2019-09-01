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
    public function clearNotifications(User $user, int ...$notificationIds): int {
        if (!\count($notificationIds)) {
            return 0;
        }

        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Notification::class, 'n')
            ->where('n.user = ?1')
            ->setParameter(1, $user)
            ->andWhere('n.id IN (?2)')
            ->setParameter(2, $notificationIds)
            ->getQuery()
            ->execute();
    }
}
