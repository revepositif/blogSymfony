<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function envoyerEmailValidation(string $toEmail, string $token): void
    {
        $url = $this->urlGenerator->generate('validation_compte', 
            ['token' => $token], 
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from('sowdian57@gmail.com')
            ->to($toEmail)
            ->subject('Validation de votre compte')
            ->html($this->getEmailTemplate(
                'Bienvenue sur notre blog !',
                'Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :',
                'Activer mon compte',
                $url
            ));

        $this->mailer->send($email);
    }

    public function envoyerEmailResetPassword(string $toEmail, string $token): void
    {
        $url = $this->urlGenerator->generate('reset_password', 
            ['token' => $token], 
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from('sowdian57@gmail.com')
            ->to($toEmail)
            ->subject('Réinitialisation de votre mot de passe')
            ->html($this->getEmailTemplate(
                'Réinitialisation de votre mot de passe',
                'Pour réinitialiser votre mot de passe, veuillez cliquer sur le lien ci-dessous :',
                'Réinitialiser mon mot de passe',
                $url
            ));

        $this->mailer->send($email);
    }

    private function getEmailTemplate(string $title, string $message, string $buttonText, string $url): string
    {
        return <<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h1 style="color: #333;">{$title}</h1>
                <p>{$message}</p>
                <p style="margin: 20px 0;">
                    <a href="{$url}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                        {$buttonText}
                    </a>
                </p>
                <p style="color: #666;">Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
                <p style="word-break: break-all; color: #007bff;">{$url}</p>
            </div>
        HTML;
    }
}