<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\CommentNotification;
use App\Entity\ArticleLike;
use App\Repository\ArticleLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/interaction')]
#[IsGranted('ROLE_USER')]
class InteractionController extends AbstractController
{
    #[Route('/{slug}/like', name: 'app_article_like')]
    #[IsGranted('ROLE_USER')]
    public function like(
        Article $article,
        EntityManagerInterface $entityManager,
        ArticleLikeRepository $likeRepository
    ): Response {
        $user = $this->getUser();
        
        // Vérifie si l'utilisateur a déjà liké l'article
        $existingLike = $likeRepository->findOneBy([
            'article' => $article,
            'user' => $user
        ]);

        if ($existingLike) {
            // Si le like existe, on le supprime (unlike)
            $entityManager->remove($existingLike);
            $message = 'Like retiré !';
        } else {
            // Sinon, on crée un nouveau like
            $like = new ArticleLike();
            $like->setUser($user);
            $like->setArticle($article);
            
            $entityManager->persist($like);
            $message = 'Article liké !';
        }

        $entityManager->flush();

        $this->addFlash('success', $message);
        
        return $this->redirectToRoute('app_article_show', [
            'slug' => $article->getSlug()
        ]);
    }

    #[Route('/{slug}/comment', name: 'app_article_comment')]
    #[IsGranted('ROLE_USER')]
    public function comment(
        Request $request,
        Article $article,
        EntityManagerInterface $entityManager
    ): Response {
        $contenu = $request->request->get('contenu');
        
        if (!$contenu) {
            $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('app_article_show', ['slug' => $article->getSlug()]);
        }

        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setAuteur($this->getUser());
        $comment->setContenu($contenu);
        $comment->setVu(true); // Un nouveau commentaire principal est toujours considéré comme "vu"
        
        $entityManager->persist($comment);
        $entityManager->flush();

        $this->addFlash('success', 'Votre commentaire a été publié !');

        return $this->redirectToRoute('app_article_show', [
            'slug' => $article->getSlug()
        ]);
    }

    #[Route('/comment/{id}/mark-read', name: 'app_comment_mark_as_read')]
    #[IsGranted('ROLE_USER')]
    public function markAsRead(
        Comment $comment,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier que l'utilisateur est bien l'auteur du commentaire parent
        if (!$comment->getParent() || $comment->getParent()->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à marquer ce commentaire comme lu.');
        }

        // Trouver et marquer la notification comme lue
        $notification = $entityManager->getRepository(CommentNotification::class)->findOneBy([
            'comment' => $comment,
            'destinataire' => $this->getUser()
        ]);

        if ($notification) {
            $notification->setIsRead(true);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_article_show', [
            'slug' => $comment->getArticle()->getSlug()
        ]);
    }

    #[Route('/comment/{id}/reply', name: 'app_comment_reply')]
    #[IsGranted('ROLE_USER')]
    public function reply(
        Request $request,
        Comment $parentComment,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier que l'utilisateur ne répond pas à son propre commentaire
        if ($parentComment->getAuteur() === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas répondre à votre propre commentaire.');
            return $this->redirectToRoute('app_article_show', [
                'slug' => $parentComment->getArticle()->getSlug()
            ]);
        }

        $contenu = $request->request->get('contenu');
        
        if (!$contenu) {
            $this->addFlash('error', 'La réponse ne peut pas être vide.');
            return $this->redirectToRoute('app_article_show', [
                'slug' => $parentComment->getArticle()->getSlug()
            ]);
        }

        $comment = new Comment();
        $comment->setArticle($parentComment->getArticle());
        $comment->setAuteur($this->getUser());
        $comment->setParent($parentComment);
        $comment->setContenu($contenu);

        $entityManager->persist($comment);

        // Créer des notifications pour tous les participants de la conversation
        $participantsNotified = [];

        // Notification pour l'auteur du commentaire parent direct
        $parentAuthor = $parentComment->getAuteur();
        if ($parentAuthor !== $this->getUser() && !in_array($parentAuthor->getId(), $participantsNotified)) {
            $notification = new CommentNotification();
            $notification->setDestinataire($parentAuthor);
            $notification->setComment($comment);
            $entityManager->persist($notification);
            $participantsNotified[] = $parentAuthor->getId();
        }

        // Si le commentaire parent a lui-même un parent (c'est une réponse à une réponse)
        $topLevelComment = $parentComment;
        while ($topLevelComment->getParent()) {
            $topLevelComment = $topLevelComment->getParent();
            $topLevelAuthor = $topLevelComment->getAuteur();
            
            // Notifier l'auteur du commentaire racine s'il n'est pas déjà notifié
            if ($topLevelAuthor !== $this->getUser() && !in_array($topLevelAuthor->getId(), $participantsNotified)) {
                $notification = new CommentNotification();
                $notification->setDestinataire($topLevelAuthor);
                $notification->setComment($comment);
                $entityManager->persist($notification);
                $participantsNotified[] = $topLevelAuthor->getId();
            }
        }

        // Notifier tous les autres participants à la conversation (qui ont répondu)
        foreach ($topLevelComment->getReponses() as $reply) {
            $replyAuthor = $reply->getAuteur();
            if ($replyAuthor !== $this->getUser() && !in_array($replyAuthor->getId(), $participantsNotified)) {
                $notification = new CommentNotification();
                $notification->setDestinataire($replyAuthor);
                $notification->setComment($comment);
                $entityManager->persist($notification);
                $participantsNotified[] = $replyAuthor->getId();
            }
        }
        
        $entityManager->flush();

        $this->addFlash('success', 'Votre réponse a été publiée !');
        
        return $this->redirectToRoute('app_article_show', [
            'slug' => $parentComment->getArticle()->getSlug()
        ]);
    }
}