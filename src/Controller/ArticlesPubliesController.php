<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticlesPubliesController extends AbstractController
{
    #[Route('/articles', name: 'app_articles_publies')]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('articles_publies/index.html.twig', [
            'articles' => $articleRepository->findPublishedArticles(),
        ]);
    }
}