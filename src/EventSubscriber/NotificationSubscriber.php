<?php

namespace App\EventSubscriber;

use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class NotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationService $notificationService,
        private Environment $twig
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        $this->twig->addGlobal('notifications_unread', $this->notificationService->getUnreadCount());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}