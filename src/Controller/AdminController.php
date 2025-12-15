<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentNotificationRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        ArticleRepository $articleRepository,
        CommentRepository $commentRepository,
        CategoryRepository $categoryRepository
    ): Response
    {
        $users = $userRepository->findBy([], ['date_creation' => 'DESC']);
        $articles = $articleRepository->findBy([], ['date_creation' => 'DESC']);
        $comments = $commentRepository->findBy([], ['date_creation' => 'DESC']);
        
        $totalUsers = $userRepository->count([]);
        $totalArticles = $articleRepository->count(['statut' => Article::STATUS_PUBLISHED]);
        $totalComments = $commentRepository->count([]);
        $totalCategories = $categoryRepository->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'users' => $users,
            'articles' => $articles,
            'comments' => $comments,
            'totalUsers' => $totalUsers,
            'totalArticles' => $totalArticles,
            'totalComments' => $totalComments,
            'totalCategories' => $totalCategories,
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy([], ['date_creation' => 'DESC']);

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/articles', name: 'admin_articles')]
    public function articles(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findBy([], ['date_creation' => 'DESC']);

        return $this->render('admin/articles.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/comments', name: 'admin_comments')]
    public function comments(CommentRepository $commentRepository): Response
    {
        $comments = $commentRepository->findBy([], ['date_creation' => 'DESC']);

        return $this->render('admin/comments.html.twig', [
            'comments' => $comments,
        ]);
    }

    #[Route('/user/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Ne pas supprimer l'utilisateur courant
            if ($user !== $this->getUser()) {
                $entityManager->remove($user);
                $entityManager->flush();
                $this->addFlash('success', 'Utilisateur supprimé avec succès.');
            } else {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            }
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/article/{id}/delete', name: 'admin_article_delete', methods: ['POST'])]
    public function deleteArticle(
        Request $request, 
        Article $article, 
        EntityManagerInterface $entityManager,
        CommentNotificationRepository $notificationRepository
    ): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            // 1. Supprimer les vues de l'article
            foreach ($article->getViews() as $view) {
                $entityManager->remove($view);
            }
            
            // 2. Supprimer les likes de l'article
            foreach ($article->getLikes() as $like) {
                $entityManager->remove($like);
            }
            
            // 3. Supprimer les notifications liées aux commentaires de l'article
            foreach ($article->getComments() as $comment) {
                $notifications = $notificationRepository->findBy(['comment' => $comment]);
                foreach ($notifications as $notification) {
                    $entityManager->remove($notification);
                }
            }
            
            // 4. Supprimer les commentaires de l'article
            foreach ($article->getComments() as $comment) {
                $entityManager->remove($comment);
            }
            
            // 5. Finalement, supprimer l'article
            $entityManager->remove($article);
            $entityManager->flush();
            
            $this->addFlash('success', 'Article supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_articles');
    }

    #[Route('/comment/{id}/delete', name: 'admin_comment_delete', methods: ['POST'])]
    public function deleteComment(
        Request $request, 
        Comment $comment, 
        EntityManagerInterface $entityManager,
        CommentNotificationRepository $notificationRepository
    ): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            // Supprimer d'abord les notifications associées
            $notifications = $notificationRepository->findBy(['comment' => $comment]);
            foreach ($notifications as $notification) {
                $entityManager->remove($notification);
            }
            
            // Ensuite supprimer le commentaire
            $entityManager->remove($comment);
            $entityManager->flush();
            
            $this->addFlash('success', 'Commentaire supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_comments');
    }

    #[Route('/comment/{id}/approve', name: 'admin_comment_approve', methods: ['POST'])]
    public function approveComment(
        Request $request, 
        Comment $comment, 
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('approve'.$comment->getId(), $request->request->get('_token'))) {
            $comment->setStatut('approuve');
            $entityManager->flush();
            
            $this->addFlash('success', 'Commentaire approuvé avec succès.');
        }

        return $this->redirectToRoute('admin_comments');
    }

    #[Route('/comment/{id}/reject', name: 'admin_comment_reject', methods: ['POST'])]
    public function rejectComment(
        Request $request, 
        Comment $comment, 
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('reject'.$comment->getId(), $request->request->get('_token'))) {
            $comment->setStatut('rejete');
            $entityManager->flush();
            
            $this->addFlash('success', 'Commentaire rejeté avec succès.');
        }

        return $this->redirectToRoute('admin_comments');
    }

    #[Route('/article/{id}/reject', name: 'admin_article_reject', methods: ['POST'])]
    public function rejectArticle(
        Request $request, 
        Article $article, 
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('reject'.$article->getId(), $request->request->get('_token'))) {
            $article->setStatut(Article::STATUS_REJECTED);
            $entityManager->flush();
            
            $this->addFlash('success', 'Article rejeté avec succès.');
        }

        return $this->redirectToRoute('admin_articles');
    }

    #[Route('/article/{id}/approve', name: 'admin_article_approve', methods: ['POST'])]
    public function approveArticle(
        Request $request, 
        Article $article, 
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('approve'.$article->getId(), $request->request->get('_token'))) {
            $article->setStatut(Article::STATUS_PUBLISHED);
            if (!$article->getDatePublication()) {
                $article->setDatePublication(new \DateTimeImmutable());
            }
            $entityManager->flush();
            
            $this->addFlash('success', 'Article réactivé avec succès.');
        }

        return $this->redirectToRoute('admin_articles');
    }
}