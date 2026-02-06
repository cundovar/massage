<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contact')]
final class ContactController extends AbstractController
{
    public function __construct(private readonly LoggerInterface $logger)
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
        if ($name == '') {
            $errors['name'] = 'Name is required.';
        }

        if ($email == '') {
            $errors['email'] = 'Email is required.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email format is invalid.';
        }

        if ($message == '') {
            $errors['message'] = 'Message is required.';
        }

        if ($errors !== []) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->logger->info('New contact request received.', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
        ]);

        return $this->json([
            'status' => 'accepted',
            'message' => 'Contact request received.',
        ], Response::HTTP_ACCEPTED);
    }
}
