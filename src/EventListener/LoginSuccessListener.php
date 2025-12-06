<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\EmailService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginSuccessListener
{
    public function __construct(
        private EmailService $emailService,
        private RequestStack $requestStack,
        private bool $sendWelcomeEmail = true, // Set to false to disable
        private bool $sendLoginNotification = false // Set to true to enable security notifications
    ) {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        if (!$user instanceof User) {
            return;
        }

        // Get IP address for security notification
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request?->getClientIp();

        try {
            // Send welcome email (enabled by default)
            if ($this->sendWelcomeEmail) {
                $this->emailService->sendWelcomeEmail($user);
            }

            // Send login notification (disabled by default, enable for security)
            if ($this->sendLoginNotification) {
                $this->emailService->sendLoginNotification($user, $ipAddress);
            }
        } catch (\Exception $e) {
            // Log the error but don't block the login process
            // You can use a logger service here if needed
            error_log('Failed to send login email: ' . $e->getMessage());
        }
    }
}
