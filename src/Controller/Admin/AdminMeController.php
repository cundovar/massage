<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
final class AdminMeController extends AbstractController
{
    #[Route('/me', name: 'api_admin_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof AdminUser) {
            return $this->json(['error' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
        ]);
    }
}
