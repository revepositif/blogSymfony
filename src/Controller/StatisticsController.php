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
        $articleStats = [];
        
        foreach ($articles as $article) {
            $totalLikes += count($article->getLikes());
            $totalComments += count($article->getComments());
            
            $articleStats[] = [
                'titre' => $article->getTitre(),
                'slug' => $article->getSlug(),
                'likes' => count($article->getLikes()),
                'comments' => count($article->getComments()),
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
            'articleStats' => $articleStats,
            'userComments' => $userComments
        ]);
    }
}