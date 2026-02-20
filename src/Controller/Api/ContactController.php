<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contact')]
final class ContactController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly NotificationService $notificationService,
    )
    {
    }

    #[Route('', name: 'api_contact_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $phone = isset($payload['phone']) ? trim((string) $payload['phone']) : null;
        $message = trim((string) ($payload['message'] ?? ''));

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Le nom est requis.';
        }

        if ($email === '') {
            $errors['email'] = "L'email est requis.";
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = "Format d'email invalide.";
        }

        if ($message === '') {
            $errors['message'] = 'Le message est requis.';
        }

        if ($errors !== []) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $this->notificationService->sendContactNotification($name, $email, $message, $phone);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to send contact notification email.', [
                'error' => $exception->getMessage(),
                'name' => $name,
                'email' => $email,
            ]);
        }

        $this->logger->info('New contact request received.', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ]);

        return $this->json([
            'status' => 'accepted',
            'message' => 'Votre message a bien ete envoye.',
        ], Response::HTTP_ACCEPTED);
    }
}
