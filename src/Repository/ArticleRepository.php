<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Trouve un article par son slug
     */
    public function findBySlug(string $slug): ?Article
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les articles publiés avec pagination
     */
    public function findPublishedArticles(int $page = 1, int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.statut = :statut')
            ->andWhere('a.date_publication IS NOT NULL')
            ->andWhere('a.date_publication <= :now')
            ->setParameter('statut', 'publie')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.date_publication', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les articles d'un auteur spécifique
     */
    public function findByAuthor(User $user, bool $publishedOnly = false): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.auteur = :user')
            ->setParameter('user', $user)
            ->orderBy('a.date_creation', 'DESC');

        if ($publishedOnly) {
            $qb->andWhere('a.statut = :statut')
               ->andWhere('a.date_publication IS NOT NULL')
               ->andWhere('a.date_publication <= :now')
               ->setParameter('statut', 'publie')
               ->setParameter('now', new \DateTimeImmutable());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les articles par catégorie
     */
    public function findByCategory(string $categorySlug): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.categories', 'c')
            ->andWhere('c.slug = :slug')
            ->andWhere('a.statut = :statut')
            ->andWhere('a.date_publication IS NOT NULL')
            ->andWhere('a.date_publication <= :now')
            ->setParameter('slug', $categorySlug)
            ->setParameter('statut', 'publie')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.date_publication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les articles les plus populaires (par likes)
     */
    public function findMostPopularArticles(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->select('a', 'COUNT(l.id) as likeCount')
            ->leftJoin('a.likes', 'l')
            ->andWhere('a.statut = :statut')
            ->andWhere('a.date_publication IS NOT NULL')
            ->andWhere('a.date_publication <= :now')
            ->setParameter('statut', 'publie')
            ->setParameter('now', new \DateTimeImmutable())
            ->groupBy('a.id')
            ->orderBy('likeCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche d'articles par mot-clé
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.titre LIKE :query OR a.contenu LIKE :query')
            ->andWhere('a.statut = :statut')
            ->andWhere('a.date_publication IS NOT NULL')
            ->andWhere('a.date_publication <= :now')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('statut', 'publie')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.date_publication', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total d'articles publiés
     */
    public function countPublishedArticles(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.statut = :statut')
            ->andWhere('a.date_publication IS NOT NULL')
            ->andWhere('a.date_publication <= :now')
            ->setParameter('statut', 'publie')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Article[] Returns an array of Article objects
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

    //    public function findOneBySomeField($value): ?Article
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}