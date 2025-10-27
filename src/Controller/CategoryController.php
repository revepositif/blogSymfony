<?php

namespace App\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories')]
#[IsGranted('ROLE_USER')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'app_category_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager
            ->getRepository(Category::class)
            ->findAll();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'app_category_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            
            if (!empty($name)) {
                $category = new Category();
                $category->setNom($name);
                
                // Génération du slug
                $timestamp = date('YmdHis');
                $category->setSlug($slugger->slug($name . '-' . $timestamp)->lower());
                
                $entityManager->persist($category);
                $entityManager->flush();
                
                $this->addFlash('success', 'Catégorie créée avec succès !');
                return $this->redirectToRoute('app_category_index');
            }
            
            $this->addFlash('error', 'Le nom de la catégorie ne peut pas être vide.');
        }
        
        return $this->render('category/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'app_category_edit')]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            
            if (!empty($name)) {
                $category->setNom($name);
                
                // Mise à jour du slug
                $timestamp = date('YmdHis');
                $category->setSlug($slugger->slug($name . '-' . $timestamp)->lower());
                
                $entityManager->flush();
                
                $this->addFlash('success', 'Catégorie modifiée avec succès !');
                return $this->redirectToRoute('app_category_index');
            }
            
            $this->addFlash('error', 'Le nom de la catégorie ne peut pas être vide.');
        }
        
        return $this->render('category/edit.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            // Vérifier si la catégorie a des articles
            if (!$category->getArticles()->isEmpty()) {
                $this->addFlash('error', 'Cette catégorie ne peut pas être supprimée car elle contient des articles.');
                return $this->redirectToRoute('app_category_index');
            }
            
            $entityManager->remove($category);
            $entityManager->flush();
            
            $this->addFlash('success', 'Catégorie supprimée avec succès !');
        }

        return $this->redirectToRoute('app_category_index');
    }
}