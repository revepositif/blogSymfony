<?php

namespace App\Service;

use App\Repository\CommentNotificationRepository;
use Symfony\Component\Security\Core\Security;

class NotificationService
{
    public function __construct(
        private CommentNotificationRepository $notificationRepository,
        private Security $security
    ) {}

    public function getUnreadCount(): int
    {
        if (!$this->security->getUser()) {
            return 0;
        }

        return count($this->notificationRepository->findUnreadByUser($this->security->getUser()));
    }
}