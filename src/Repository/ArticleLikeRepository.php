<?php

namespace App\Repository;

use App\Entity\ArticleLike;
use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleLike>
 */
class ArticleLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleLike::class);
    }

    /**
     * Vérifie si un utilisateur a déjà liké un article
     */
    public function userHasLikedArticle(User $user, Article $article): bool
    {
        $like = $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->andWhere('l.article = :article')
            ->setParameter('user', $user)
            ->setParameter('article', $article)
            ->getQuery()
            ->getOneOrNullResult();

        return $like !== null;
    }

    /**
     * Trouve le like d'un utilisateur sur un article
     */
    public function findUserLikeForArticle(User $user, Article $article): ?ArticleLike
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->andWhere('l.article = :article')
            ->setParameter('user', $user)
            ->setParameter('article', $article)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte le nombre de likes d'un article
     */
    public function countLikesForArticle(Article $article): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.article = :article')
            ->setParameter('article', $article)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les articles les plus likés
     */
    public function findMostLikedArticles(int $limit = 5): array
    {
        return $this->createQueryBuilder('l')
            ->select('a', 'COUNT(l.id) as likeCount')
            ->join('l.article', 'a')
            ->andWhere('a.statut = :statut')
            ->andWhere('a.date_publication IS NOT NULL')
            ->setParameter('statut', 'publie')
            ->groupBy('a.id')
            ->orderBy('likeCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return ArticleLike[] Returns an array of ArticleLike objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ArticleLike
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}