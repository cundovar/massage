<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly AdminUserRepository $adminUserRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email format is invalid.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Password must contain at least 8 characters.';
        }

        if ($errors !== []) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Public registration is only allowed for the very first admin account.
        if ($this->adminUserRepository->count([]) > 0) {
            return $this->json([
                'error' => 'Registration is disabled once an admin account exists. Use backoffice login.',
            ], Response::HTTP_FORBIDDEN);
        }

        $adminUser = (new AdminUser())
            ->setName($name)
            ->setEmail($email);

        $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, $password));

        $this->entityManager->persist($adminUser);
        $this->entityManager->flush();

        return $this->json([
            'id' => $adminUser->getId(),
            'email' => $adminUser->getEmail(),
            'name' => $adminUser->getName(),
            'message' => 'Admin account created. You can now login.',
        ], Response::HTTP_CREATED);
    }
}
