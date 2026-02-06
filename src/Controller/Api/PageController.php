<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/pages')]
final class PageController extends AbstractController
{
    public function __construct(private readonly PageRepository $pageRepository)
    {
    }

    #[Route('/{slug}', name: 'api_pages_show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $page = $this->pageRepository->findOneBy(['slug' => $slug]);

        if ($page === null) {
            return $this->json(
                ['error' => sprintf('Page "%s" not found.', $slug)],
                Response::HTTP_NOT_FOUND
            );
        }

        $sections = [];
        foreach ($page->getSections() as $section) {
            $sections[$section->getSectionKey()] = [
                'title' => $section->getTitle(),
                'content' => $section->getContent(),
            ];
        }

        return $this->json([
            'slug' => $page->getSlug(),
            'title' => $page->getTitle(),
            'metaTitle' => $page->getMetaTitle(),
            'metaDescription' => $page->getMetaDescription(),
            'sections' => $sections,
        ]);
    }
}
