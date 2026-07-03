<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\PasswordResetService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/password')]
final class PasswordResetController extends AbstractController
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/forgot', name: 'api_password_forgot', methods: ['POST'])]
    public function forgot(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            try {
                $this->passwordResetService->requestReset($email);
            } catch (\Throwable $exception) {
                $this->logger->error('Unable to send admin password reset email.', [
                    'exception' => $exception,
                ]);
            }
        }

        return $this->json([
            'message' => 'If an admin account exists, a password reset email has been sent.',
        ]);
    }

    #[Route('/reset', name: 'api_password_reset', methods: ['POST'])]
    public function reset(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $token = trim((string) ($payload['token'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $errors = [];

        if ($token === '') {
            $errors['token'] = 'Token is required.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Password must contain at least 8 characters.';
        }

        if ($errors !== []) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$this->passwordResetService->resetPassword($token, $password)) {
            return $this->json(['error' => 'Invalid or expired reset token.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['message' => 'Password has been reset.']);
    }
}
