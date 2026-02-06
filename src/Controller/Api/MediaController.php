<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/media')]
final class MediaController extends AbstractController
{
    #[Route('', name: 'api_media_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Not implemented yet',
            'items' => [],
        ]);
    }
}
