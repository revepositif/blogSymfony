<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\InscriptionType;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class InscriptionController extends AbstractController
{
    #[Route('/inscription', name: 'inscription')]
    public function inscription(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
        $user = new User();
        $form = $this->createForm(InscriptionType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du mot de passe
            $user->setPassword($passwordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            ));

            // Génération du token
            $user->setTokenVerification(bin2hex(random_bytes(32)));
            
            // Sauvegarde
            $entityManager->persist($user);
            $entityManager->flush();

            // Envoi de l'email
            $emailService->envoyerEmailValidation($user->getEmail(), $user->getTokenVerification());

            $this->addFlash('success', 'Inscription réussie ! Vérifiez vos emails pour activer votre compte.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('inscription/formulaire.html.twig', [
            'form' => $form->createView()
        ], new Response(
            null,
            $form->isSubmitted() && !$form->isValid() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK
        ));
    }

    #[Route('/validation/{token}', name: 'validation_compte')]
    public function validation(
        string $token,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $entityManager->getRepository(User::class)->findOneBy([
            'token_verification' => $token
        ]);

        if (!$user) {
            throw $this->createNotFoundException('Token invalide');
        }

        $user->setEstVerifie(true);
        $user->setTokenVerification(null);
        $entityManager->flush();

        $this->addFlash('success', 'Compte activé avec succès ! Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }
}