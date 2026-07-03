<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetService
{
    private const TOKEN_TTL = '+1 hour';

    public function __construct(
        private readonly AdminUserRepository $adminUserRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly string $frontendUrl,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
    }

    public function requestReset(string $email): void
    {
        $adminUser = $this->adminUserRepository->findOneBy(['email' => strtolower(trim($email))]);

        if (!$adminUser instanceof AdminUser) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $now = new \DateTimeImmutable();

        $adminUser
            ->setPasswordResetTokenHash($this->hashToken($token))
            ->setPasswordResetRequestedAt($now)
            ->setPasswordResetExpiresAt($now->modify(self::TOKEN_TTL));

        $this->entityManager->flush();
        $this->sendResetEmail($adminUser, $token);
    }

    public function resetPassword(string $token, string $plainPassword): bool
    {
        $adminUser = $this->adminUserRepository->findOneBy([
            'passwordResetTokenHash' => $this->hashToken($token),
        ]);

        if (!$adminUser instanceof AdminUser) {
            return false;
        }

        $expiresAt = $adminUser->getPasswordResetExpiresAt();
        if ($expiresAt === null || $expiresAt <= new \DateTimeImmutable()) {
            $adminUser->clearPasswordResetToken();
            $this->entityManager->flush();

            return false;
        }

        $adminUser
            ->setPassword($this->passwordHasher->hashPassword($adminUser, $plainPassword))
            ->clearPasswordResetToken();

        $this->entityManager->flush();

        return true;
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function sendResetEmail(AdminUser $adminUser, string $token): void
    {
        $resetUrl = rtrim($this->frontendUrl, '/') . '/admin/reset-password?token=' . urlencode($token);
        $safeName = htmlspecialchars($adminUser->getName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeResetUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $message = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($adminUser->getEmail())
            ->subject('Reinitialisation de votre mot de passe admin')
            ->html(<<<HTML
                <!DOCTYPE html>
                <html lang="fr">
                <body style="font-family: Arial, sans-serif; color: #292524; line-height: 1.6;">
                    <p>Bonjour {$safeName},</p>
                    <p>Une demande de reinitialisation du mot de passe admin a ete effectuee.</p>
                    <p>
                        <a href="{$safeResetUrl}" style="display: inline-block; padding: 10px 14px; background: #f59e0b; color: #ffffff; text-decoration: none; border-radius: 6px;">
                            Choisir un nouveau mot de passe
                        </a>
                    </p>
                    <p>Ce lien expire dans 1 heure. Si vous n'etes pas a l'origine de cette demande, ignorez cet email.</p>
                </body>
                </html>
                HTML)
            ->text("Bonjour {$adminUser->getName()},\n\nUtilisez ce lien pour choisir un nouveau mot de passe admin :\n{$resetUrl}\n\nCe lien expire dans 1 heure.");

        $this->mailer->send($message);
    }
}
