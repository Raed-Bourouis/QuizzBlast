<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail = 'bechirzamouri06@gmail.com', // Use your actual Gmail
        private string $fromName = 'QuizzBlast'
    ) {
    }

    /**
     * Send a welcome email when user logs in
     */
    public function sendWelcomeEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to((string) $user->getEmail())
            ->subject('Welcome to QuizzBlast!')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'user' => $user,
                'loginTime' => new \DateTimeImmutable(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send a notification email for successful login
     */
    public function sendLoginNotification(User $user, string $ipAddress = null): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to((string) $user->getEmail())
            ->subject('New Login to Your QuizzBlast Account')
            ->htmlTemplate('emails/login_notification.html.twig')
            ->context([
                'user' => $user,
                'loginTime' => new \DateTimeImmutable(),
                'ipAddress' => $ipAddress,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send a custom email
     */
    public function sendCustomEmail(
        string $to,
        string $subject,
        string $template,
        array $context = []
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        $this->mailer->send($email);
    }

    /**
     * Set custom from address
     */
    public function setFromEmail(string $email, string $name = null): self
    {
        $this->fromEmail = $email;
        if ($name !== null) {
            $this->fromName = $name;
        }
        return $this;
    }
}
