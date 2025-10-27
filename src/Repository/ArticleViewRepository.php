<?php

namespace App\Repository;

use App\Entity\ArticleView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleView>
 *
 * @method ArticleView|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArticleView|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArticleView[]    findAll()
 * @method ArticleView[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleView::class);
    }

    public function hasUserViewedArticle(\App\Entity\User $user, \App\Entity\Article $article): bool
    {
        $result = $this->findOneBy([
            'user' => $user,
            'article' => $article
        ]);
        
        return $result !== null;
    }
}