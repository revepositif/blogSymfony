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
    public const STATUS_DRAFT = 'brouillon';
    public const STATUS_PUBLISHED = 'publie';
    public const STATUS_ARCHIVED = 'archive';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Récupère tous les articles d'un utilisateur selon leur statut
     */
    public function findByUserAndStatus(User $user, string $status): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.auteur = :user')
            ->andWhere('a.statut = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->orderBy('a.date_creation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les articles publiés avec pagination
     */
    public function findPublishedArticles(int $page = 1, int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.statut = :status')
            ->andWhere('a.date_publication IS NOT NULL')
            ->andWhere('a.date_publication <= :now')
            ->setParameter('status', self::STATUS_PUBLISHED)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.date_publication', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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
     * Compte le nombre d'articles par statut pour un utilisateur
     */
    public function countByUserAndStatus(User $user, string $status): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.auteur = :user')
            ->andWhere('a.statut = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Recherche des articles publiés selon un terme de recherche
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.statut = :status')
            ->andWhere('a.date_publication IS NOT NULL')
            ->andWhere('a.date_publication <= :now')
            ->andWhere('LOWER(a.titre) LIKE LOWER(:query) OR LOWER(a.contenu) LIKE LOWER(:query)')
            ->setParameter('status', self::STATUS_PUBLISHED)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.date_publication', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
