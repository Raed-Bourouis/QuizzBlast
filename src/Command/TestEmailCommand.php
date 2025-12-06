<?php

namespace App\Command;

use App\Service\EmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:test-email',
    description: 'Send a test email to verify email configuration',
)]
class TestEmailCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('to', InputArgument::REQUIRED, 'Email address to send test email to')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $toEmail = $input->getArgument('to');

        $io->title('Testing Email Configuration');
        $io->info('Attempting to send test email...');

        try {
            $email = (new TemplatedEmail())
                ->from(new Address('bechirzamouri06@gmail.com', 'QuizzBlast Test'))
                ->to($toEmail)
                ->subject('Test Email from QuizzBlast')
                ->html('<h1>Test Email</h1><p>If you received this, your email configuration is working!</p>');

            $this->mailer->send($email);

            $io->success(sprintf('Test email successfully sent to: %s', $toEmail));
            $io->note('Check your inbox (and spam folder) for the test email.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to send email!');
            $io->error($e->getMessage());
            $io->note('Check the error message above for details.');

            return Command::FAILURE;
        }
    }
}
