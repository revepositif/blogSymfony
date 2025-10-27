<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Service\ImageUploader;

#[Route('/article')]
#[IsGranted('ROLE_USER')]
class ArticleController extends AbstractController
{
    public const STATUS_DRAFT = 'brouillon';
    public const STATUS_PUBLISHED = 'publie';
    public const STATUS_ARCHIVED = 'archive';
    #[Route('/', name: 'app_article_index')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_article_my_articles');
    }

    #[Route('/mes-articles', name: 'app_article_my_articles')]
    public function myArticles(ArticleRepository $articleRepository): Response
    {
        $user = $this->getUser();
        
        return $this->render('article/my_articles.html.twig', [
            'drafts' => $articleRepository->findByUserAndStatus($user, ArticleRepository::STATUS_DRAFT),
            'published' => $articleRepository->findByUserAndStatus($user, ArticleRepository::STATUS_PUBLISHED),
            'archived' => $articleRepository->findByUserAndStatus($user, ArticleRepository::STATUS_ARCHIVED),
            'stats' => [
                'drafts' => $articleRepository->countByUserAndStatus($user, ArticleRepository::STATUS_DRAFT),
                'published' => $articleRepository->countByUserAndStatus($user, ArticleRepository::STATUS_PUBLISHED),
                'archived' => $articleRepository->countByUserAndStatus($user, ArticleRepository::STATUS_ARCHIVED),
            ]
        ]);
    }

    #[Route('/nouveau', name: 'app_article_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ImageUploader $imageUploader
    ): Response {
        $article = new Article();
        $article->setAuteur($this->getUser());
        
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {

            
            // Génération du slug pour l'article
            $timestamp = date('YmdHis');
            $article->setSlug($slugger->slug($article->getTitre() . '-' . $timestamp)->lower());

            // Gestion de la catégorie
            $categoryName = $form->get('nouvelle_categorie')->getData();
            if ($categoryName) {
                $timestamp = date('YmdHis');
                $category = new Category();
                $category->setNom($categoryName);
                $category->setSlug($slugger->slug($categoryName . '-' . $timestamp)->lower());
                $entityManager->persist($category);
                $article->setCategory($category);
            } else {
                // Si aucune catégorie n'est spécifiée, utiliser la catégorie par défaut
                $defaultCategory = $entityManager->getRepository(Category::class)->findOneBy(['slug' => 'non-categorise']);
                if (!$defaultCategory) {
                    $defaultCategory = new Category();
                    $defaultCategory->setNom('Non catégorisé');
                    $defaultCategory->setSlug('non-categorise');
                    $entityManager->persist($defaultCategory);
                }
                $article->setCategory($defaultCategory);
            }
            
            // Gestion de l'image
            if ($imageFile = $form->get('image')->getData()) {
                $imageFileName = $imageUploader->upload($imageFile);
                $article->setImage($imageFileName);
            }
            
            // Définition du statut (brouillon ou publié)
            if ($request->request->has('publish')) {
                $article->setStatut(ArticleRepository::STATUS_PUBLISHED);
                $article->setDatePublication(new \DateTimeImmutable());
                $message = 'Article publié avec succès !';
            } else {
                $article->setStatut(ArticleRepository::STATUS_DRAFT);
                $message = 'Article enregistré comme brouillon.';
            }
            
            $entityManager->persist($article);
            $entityManager->flush();
            
            $this->addFlash('success', $message);
            
            return $this->redirectToRoute('app_article_show', ['slug' => $article->getSlug()]);
        }
        
        return $this->render('article/new.html.twig', [
            'form' => $form->createView(),
            'article' => $article
        ]);
    }

    #[Route('/{slug}/publier', name: 'app_article_publish')]
    public function publish(Article $article, EntityManagerInterface $entityManager): Response
    {
        // Vérifie si l'utilisateur est l'auteur
        if ($article->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'auteur de cet article.');
        }

        $article->setStatut('publie');
        $article->setDatePublication(new \DateTimeImmutable());
        
        $entityManager->flush();

        $this->addFlash('success', 'Article publié avec succès !');
        
        return $this->redirectToRoute('app_article_show', ['slug' => $article->getSlug()]);
    }

    #[Route('/{slug}/archiver', name: 'app_article_archive')]
    public function archive(Article $article, EntityManagerInterface $entityManager): Response
    {
        // Vérifie si l'utilisateur est l'auteur
        if ($article->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'auteur de cet article.');
        }

        $article->setStatut(ArticleRepository::STATUS_ARCHIVED);
        
        $entityManager->flush();

        $this->addFlash('success', 'Article archivé avec succès !');
        
        return $this->redirectToRoute('app_article_my_articles');
    }

    #[Route('/{slug}/restaurer', name: 'app_article_restore')]
    public function restore(Article $article, EntityManagerInterface $entityManager): Response
    {
        // Vérifie si l'utilisateur est l'auteur
        if ($article->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'auteur de cet article.');
        }

        $article->setStatut(self::STATUS_DRAFT);
        
        $entityManager->flush();

        $this->addFlash('success', 'Article restauré avec succès !');
        
        return $this->redirectToRoute('app_article_show', ['slug' => $article->getSlug()]);
    }

    #[Route('/{slug}/supprimer', name: 'app_article_delete')]
    public function delete(Article $article, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Vérifie si l'utilisateur est l'auteur
        if ($article->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'auteur de cet article.');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // Suppression de l'image si elle existe
        if ($article->getImage()) {
            $imagePath = $this->getParameter('articles_directory').'/'.$article->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $entityManager->remove($article);
        $entityManager->flush();

        $this->addFlash('success', 'Article supprimé avec succès !');
        
        return $this->redirectToRoute('app_article_my_articles');
    }

    #[Route('/{slug}/edit', name: 'app_article_edit')]
    public function edit(
        Request $request,
        Article $article,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ImageUploader $imageUploader
    ): Response {
        if ($article->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'auteur de cet article.');
        }

        $form = $this->createForm(ArticleType::class, $article);
        $oldStatut = $article->getStatut(); // Sauvegarde du statut actuel
        $oldDatePublication = $article->getDatePublication(); // Sauvegarde de la date de publication
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image
            if ($imageFile = $form->get('image')->getData()) {
                $imageFileName = $imageUploader->upload($imageFile);
                $article->setImage($imageFileName);
            }

            // Gestion de la catégorie
            $categoryName = $form->get('nouvelle_categorie')->getData();
            if ($categoryName) {
                $timestamp = date('YmdHis');
                $category = new Category();
                $category->setNom($categoryName);
                $category->setSlug($slugger->slug($categoryName . '-' . $timestamp)->lower());
                $entityManager->persist($category);
                $article->setCategory($category);
            }

            // Restauration du statut et de la date de publication
            $article->setStatut($oldStatut);
            $article->setDatePublication($oldDatePublication);

            $entityManager->flush();
            
            $this->addFlash('success', 'Article modifié avec succès !');
            
            return $this->redirectToRoute('app_article_show', ['slug' => $article->getSlug()]);
        }

        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article
        ]);
    }

    #[Route('/{slug}', name: 'app_article_show')]
    public function show(
        Request $request, 
        ArticleRepository $articleRepository, 
        EntityManagerInterface $entityManager,
        string $slug
    ): Response {
        $article = $articleRepository->findBySlug($slug);
        
        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé.');
        }

        $currentUser = $this->getUser();
        $isAuthor = $currentUser && $article->getAuteur() === $currentUser;

        // Vérifie si l'article est publié ou si l'utilisateur est l'auteur
        if (!$article->isPublished() && !$isAuthor) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet article.');
        }

        // Récupération des notifications pour les commentaires
        $notifications = [];
        if ($currentUser) {
            $notificationsResult = $entityManager
                ->getRepository('App\Entity\CommentNotification')
                ->createQueryBuilder('n')
                ->where('n.destinataire = :destinataire')
                ->andWhere('n.comment IN (:comments)')
                ->setParameter('destinataire', $currentUser)
                ->setParameter('comments', $article->getComments())
                ->getQuery()
                ->getResult();

            foreach ($notificationsResult as $notification) {
                $notifications[$notification->getComment()->getId()] = $notification;
            }

            // Création du formulaire de commentaire
            $comment = new Comment();
            $comment->setArticle($article);
            $comment->setAuteur($currentUser);
            $commentForm = $this->createForm(CommentType::class, $comment)->createView();

        // Si l'utilisateur est connecté et est l'auteur et vient de la page "mes-articles"
        $fromMyArticles = $request->query->has('from') && $request->query->get('from') === 'my-articles';
        if ($isAuthor && $fromMyArticles) {
            return $this->render('article/show_author.html.twig', [
                'article' => $article,
                'notifications' => $notifications
            ]);
        }

        // Sinon, on utilise le template public
        return $this->render('article/show_public.html.twig', [
            'article' => $article,
            'commentForm' => $commentForm,
            'notifications' => $notifications
        ]);
    }
}
}