<?php

namespace App\Repository;

use App\Entity\CommentNotification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentNotification::class);
    }

    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.destinataire = :user')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->orderBy('n.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function markAllAsRead(User $user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':isRead')
            ->where('n.destinataire = :user')
            ->setParameter('isRead', true)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}