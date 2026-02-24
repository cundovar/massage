<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ImageProxyController extends AbstractController
{
    public function __construct(
        private readonly string $imagesDirectory,
    ) {
    }

    #[Route('/images/{path}', name: 'app_images_proxy', requirements: ['path' => '.+'], methods: ['GET'])]
    public function __invoke(string $path): Response
    {
        $relativePath = ltrim($path, '/');

        // Prevent directory traversal attempts.
        if (str_contains($relativePath, '..')) {
            return new Response('Not Found', Response::HTTP_NOT_FOUND);
        }

        $absolutePath = $this->imagesDirectory . DIRECTORY_SEPARATOR . $relativePath;
        $realPath = realpath($absolutePath);
        $imagesRoot = realpath($this->imagesDirectory);

        if ($realPath === false || $imagesRoot === false || !str_starts_with($realPath, $imagesRoot) || !is_file($realPath)) {
            return new Response('Not Found', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse($realPath);
    }
}
