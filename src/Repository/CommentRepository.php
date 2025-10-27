<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Trouve les commentaires approuvés d'un article
     */
    public function findAllCommentsByArticle(Article $article): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'r', 'a')
            ->leftJoin('c.reponses', 'r')
            ->leftJoin('c.auteur', 'a')
            ->andWhere('c.article = :article')
            ->setParameter('article', $article)
            ->orderBy('c.date_creation', 'ASC')
            ->addOrderBy('r.date_creation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réponses d'un commentaire
     */
    public function findRepliesByComment(Comment $comment): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent = :comment')
            ->andWhere('c.statut = :statut')
            ->setParameter('comment', $comment)
            ->setParameter('statut', 'approuve')
            ->orderBy('c.date_creation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les commentaires en attente de modération
     */
    public function findPendingComments(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.statut = :statut')
            ->setParameter('statut', 'en_attente')
            ->orderBy('c.date_creation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les commentaires d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.auteur = :user')
            ->setParameter('user', $user)
            ->orderBy('c.date_creation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de commentaires en attente
     */
    public function countPendingComments(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.statut = :statut')
            ->setParameter('statut', 'en_attente')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les derniers commentaires approuvés
     */
    public function findLatestApprovedComments(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.statut = :statut')
            ->setParameter('statut', 'approuve')
            ->orderBy('c.date_creation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Comment[] Returns an array of Comment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Comment
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}