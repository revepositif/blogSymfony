<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatisticsController extends AbstractController
{
    #[Route('/profile/statistics', name: 'app_profile_statistics')]
    public function index(
        ArticleRepository $articleRepository,
        CommentRepository $commentRepository
    ): Response {
        $user = $this->getUser();
        
        // Statistiques des articles
        $articles = $articleRepository->findBy(['auteur' => $user]);
        $totalLikes = 0;
        $totalComments = 0;
        $totalViews = 0;
        $articleStats = [];
        
        foreach ($articles as $article) {
            $likes = count($article->getLikes());
            $comments = count($article->getComments());
            $views = $article->getUniqueViewsCount();
            
            $totalLikes += $likes;
            $totalComments += $comments;
            $totalViews += $views;
            
            $articleStats[] = [
                'titre' => $article->getTitre(),
                'slug' => $article->getSlug(),
                'likes' => $likes,
                'comments' => $comments,
                'views' => $views,
                'datePublication' => $article->getDatePublication(),
                'statut' => $article->getStatut()
            ];
        }

        // Statistiques des commentaires
        $userComments = $commentRepository->findBy(['auteur' => $user]);

        return $this->render('statistics/index.html.twig', [
            'user' => $user,
            'totalArticles' => count($articles),
            'totalLikes' => $totalLikes,
            'totalComments' => $totalComments,
            'totalViews' => $totalViews,
            'articleStats' => $articleStats,
            'userComments' => $userComments
        ]);
    }
}