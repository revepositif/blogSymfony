<?php

namespace App\Controller;

use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;
use App\Form\ResendValidationEmailType;
use App\Repository\UserRepository;
use App\Service\EmailService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SecurityHelpController extends AbstractController
{
    #[Route('/renvoyer-email-validation', name: 'resend_validation_email')]
    public function resendValidationEmail(
        Request $request,
        UserRepository $userRepository,
        EmailService $emailService,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ResendValidationEmailType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user && !$user->isEstVerifie()) {
                // Générer un nouveau token
                $user->setTokenVerification(bin2hex(random_bytes(32)));
                $entityManager->persist($user);
                $entityManager->flush();

                // Envoyer l'email
                $emailService->envoyerEmailValidation($email, $user->getTokenVerification());

                $this->addFlash('success', 'Un nouvel email de validation vous a été envoyé.');
                return $this->redirectToRoute('app_login');
            }

            // Ne pas révéler si l'email existe ou non
            $this->addFlash('success', 'Si votre email est valide, vous recevrez un lien de validation.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security_help/resend_validation.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/mot-de-passe-oublie', name: 'forgot_password')]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EmailService $emailService,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $user->setResetToken(bin2hex(random_bytes(32)));
                $user->setResetTokenCreatedAt(new DateTimeImmutable());
                $user->setResetTokenExpiresAt(new DateTimeImmutable('+1 hour'));
                $entityManager->persist($user);
                $entityManager->flush();

                $emailService->envoyerEmailResetPassword($email, $user->getResetToken());
            }

            // Ne pas révéler si l'email existe ou non
            $this->addFlash('success', 'Si votre email est valide, vous recevrez un lien de réinitialisation.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security_help/forgot_password.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/reinitialiser-mot-de-passe/{token}', name: 'reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $userRepository->findOneBy(['reset_token' => $token]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new DateTimeImmutable()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('forgot_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            ));
            
            // Réinitialiser les tokens
            $user->setResetToken(null);
            $user->setResetTokenCreatedAt(null);
            $user->setResetTokenExpiresAt(null);

            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security_help/reset_password.html.twig', [
            'form' => $form->createView()
        ]);
    }
}