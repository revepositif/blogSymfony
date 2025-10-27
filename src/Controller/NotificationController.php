<?php

namespace App\Controller;

use App\Repository\CommentNotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('/', name: 'app_notifications')]
    public function index(CommentNotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        $notifications = $notificationRepository->findUnreadByUser($user);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications
        ]);
    }

    #[Route('/mark-all-read', name: 'app_notifications_mark_all_read')]
    public function markAllAsRead(CommentNotificationRepository $notificationRepository): Response
    {
        $notificationRepository->markAllAsRead($this->getUser());
        
        $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues.');
        
        return $this->redirectToRoute('app_notifications');
    }
}