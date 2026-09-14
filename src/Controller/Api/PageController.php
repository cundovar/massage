<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Page;
use App\Repository\PageRepository;
use App\Repository\PageSlugRedirectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/pages')]
final class PageController extends AbstractController
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly PageSlugRedirectRepository $pageSlugRedirectRepository,
    ) {
    }

    #[Route('/{slug}', name: 'api_pages_show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $page = $this->pageRepository->findOneBy(['slug' => $slug]);

        if ($page === null) {
            $redirect = $this->pageSlugRedirectRepository->findOneByOldSlug($slug);
            $page = $redirect?->getPage();
        }

        if ($page === null) {
            return $this->json(
                ['error' => sprintf('Page "%s" not found.', $slug)],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json($this->serializePage($page));
    }

    /** @return array<string, mixed> */
    private function serializePage(Page $page): array
    {
        $sections = [];
        foreach ($page->getSections() as $section) {
            if (!$section->isVisible()) {
                continue;
            }

            $sections[$section->getSectionKey()] = [
                'type' => $section->getType(),
                'title' => $section->getTitle(),
                'content' => $section->getContent(),
                'sortOrder' => $section->getSortOrder(),
            ];
        }

        return [
            'slug' => $page->getSlug(),
            'title' => $page->getTitle(),
            'metaTitle' => $page->getMetaTitle(),
            'metaDescription' => $page->getMetaDescription(),
            'sections' => $sections,
        ];
    }
}
