<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Trouve une catégorie par son slug
     */
    public function findBySlug(string $slug): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les catégories avec le plus d'articles
     */
    public function findMostPopularCategories(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'COUNT(a.id) as articleCount')
            ->leftJoin('c.articles', 'a')
            ->andWhere('a.statut = :statut')
            ->setParameter('statut', 'publie')
            ->groupBy('c.id')
            ->orderBy('articleCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les catégories avec leurs articles publiés
     */
    public function findAllWithPublishedArticles(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.articles', 'a')
            ->addSelect('a')
            ->andWhere('a.statut = :statut')
            ->setParameter('statut', 'publie')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Category[] Returns an array of Category objects
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

    //    public function findOneBySomeField($value): ?Category
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}