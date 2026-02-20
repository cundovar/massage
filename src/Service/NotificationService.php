<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class NotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    public function sendContactNotification(
        string $name,
        string $email,
        string $message,
        ?string $phone = null,
    ): void {
        $adminEmail = 'lnmiserey@hotmail.com';

        $emailContent = $this->buildContactEmailContent($name, $email, $message, $phone);

        $emailMessage = (new Email())
            ->from(new Address('cundo364@gmail.com', 'Site Helene Massage'))
            ->to($adminEmail)
            ->replyTo($email)
            ->subject('Nouveau message de contact - ' . $name)
            ->html($emailContent);

        $this->mailer->send($emailMessage);
    }

    private function buildContactEmailContent(
        string $name,
        string $email,
        string $message,
        ?string $phone,
    ): string {
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $safePhone = $phone !== null ? htmlspecialchars($phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
        $phoneHtml = $safePhone !== null && $safePhone !== ''
            ? "<p><strong>Telephone :</strong> {$safePhone}</p>"
            : '';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8" />
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #ffce67, #f67e54); padding: 20px; border-radius: 8px 8px 0 0; }
                .header h1 { color: white; margin: 0; font-size: 24px; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .field { margin-bottom: 15px; }
                .message { background: white; padding: 15px; border-radius: 4px; border-left: 4px solid #ffce67; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Nouveau message de contact</h1>
                </div>
                <div class="content">
                    <div class="field">
                        <p><strong>Nom :</strong> {$safeName}</p>
                    </div>
                    <div class="field">
                        <p><strong>Email :</strong> <a href="mailto:{$safeEmail}">{$safeEmail}</a></p>
                    </div>
                    {$phoneHtml}
                    <div class="field">
                        <p><strong>Message :</strong></p>
                        <div class="message">{$safeMessage}</div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
