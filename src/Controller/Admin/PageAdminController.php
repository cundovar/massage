<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Entity\PageSection;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/pages')]
final class PageAdminController extends AbstractController
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_admin_pages_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $pages = $this->pageRepository->findBy([], ['id' => 'ASC']);

        $items = array_map(static fn ($page): array => [
            'id' => $page->getId(),
            'slug' => $page->getSlug(),
            'title' => $page->getTitle(),
            'metaTitle' => $page->getMetaTitle(),
            'metaDescription' => $page->getMetaDescription(),
            'updatedAt' => $page->getUpdatedAt()->format(DATE_ATOM),
        ], $pages);

        return $this->json(['items' => $items]);
    }

    #[Route('', name: 'api_admin_pages_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $slug = trim((string) ($payload['slug'] ?? ''));
        $title = trim((string) ($payload['title'] ?? ''));

        if ($slug === '' || $title === '') {
            return $this->json(['error' => 'slug et title requis'], Response::HTTP_BAD_REQUEST);
        }

        $existingPage = $this->pageRepository->findOneBy(['slug' => $slug]);
        if ($existingPage !== null) {
            return $this->json(['error' => 'Ce slug existe déjà'], Response::HTTP_CONFLICT);
        }

        $now = new \DateTimeImmutable();
        $page = (new Page())
            ->setSlug($slug)
            ->setTitle($title)
            ->setMetaTitle(isset($payload['metaTitle']) ? trim((string) $payload['metaTitle']) : $title)
            ->setMetaDescription(isset($payload['metaDescription']) ? trim((string) $payload['metaDescription']) : null)
            ->setShowInNav(true)
            ->setNavOrder($this->pageRepository->count([]))
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        $heroSection = (new PageSection())
            ->setSectionKey('hero')
            ->setTitle($title)
            ->setContent([
                'title' => $title,
                'image' => '/images/default/hero-1.jpg',
            ])
            ->setSortOrder(0)
            ->setUpdatedAt($now);

        $page->addSection($heroSection);

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        return $this->json([
            'id' => $page->getId(),
            'slug' => $page->getSlug(),
            'title' => $page->getTitle(),
            'metaTitle' => $page->getMetaTitle(),
            'metaDescription' => $page->getMetaDescription(),
            'updatedAt' => $page->getUpdatedAt()->format(DATE_ATOM),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{slug}', name: 'api_admin_pages_show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $page = $this->pageRepository->findOneBy(['slug' => $slug]);
        if ($page === null) {
            return $this->json(['error' => 'Page not found.'], Response::HTTP_NOT_FOUND);
        }

        $sections = [];
        foreach ($page->getSections() as $section) {
            $sections[] = [
                'key' => $section->getSectionKey(),
                'title' => $section->getTitle(),
                'content' => $section->getContent(),
                'sortOrder' => $section->getSortOrder(),
                'updatedAt' => $section->getUpdatedAt()->format(DATE_ATOM),
            ];
        }

        return $this->json([
            'id' => $page->getId(),
            'slug' => $page->getSlug(),
            'title' => $page->getTitle(),
            'metaTitle' => $page->getMetaTitle(),
            'metaDescription' => $page->getMetaDescription(),
            'sections' => $sections,
            'updatedAt' => $page->getUpdatedAt()->format(DATE_ATOM),
        ]);
    }

    #[Route('/{slug}', name: 'api_admin_pages_update', methods: ['PUT'])]
    public function update(string $slug, Request $request): JsonResponse
    {
        $page = $this->pageRepository->findOneBy(['slug' => $slug]);
        if ($page === null) {
            return $this->json(['error' => 'Page not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('title', $payload)) {
            $title = trim((string) $payload['title']);
            if ($title === '') {
                return $this->json(['errors' => ['title' => 'Title cannot be empty.']], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $page->setTitle($title);
        }

        if (array_key_exists('metaTitle', $payload)) {
            $metaTitle = $payload['metaTitle'];
            $page->setMetaTitle($metaTitle !== null ? trim((string) $metaTitle) : null);
        }

        if (array_key_exists('metaDescription', $payload)) {
            $metaDescription = $payload['metaDescription'];
            $page->setMetaDescription($metaDescription !== null ? trim((string) $metaDescription) : null);
        }

        $page->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json([
            'id' => $page->getId(),
            'slug' => $page->getSlug(),
            'title' => $page->getTitle(),
            'metaTitle' => $page->getMetaTitle(),
            'metaDescription' => $page->getMetaDescription(),
            'updatedAt' => $page->getUpdatedAt()->format(DATE_ATOM),
        ]);
    }

    #[Route('/{slug}/sections/{key}', name: 'api_admin_pages_sections_update', methods: ['PUT'])]
    public function updateSection(string $slug, string $key, Request $request): JsonResponse
    {
        $page = $this->pageRepository->findOneBy(['slug' => $slug]);
        if ($page === null) {
            return $this->json(['error' => 'Page not found.'], Response::HTTP_NOT_FOUND);
        }

        $targetSection = null;
        foreach ($page->getSections() as $section) {
            if ($section->getSectionKey() === $key) {
                $targetSection = $section;
                break;
            }
        }

        if (!$targetSection instanceof PageSection) {
            return $this->json(['error' => 'Section not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('title', $payload)) {
            $title = $payload['title'];
            $targetSection->setTitle($title !== null ? trim((string) $title) : null);
        }

        if (array_key_exists('content', $payload)) {
            if (!is_array($payload['content'])) {
                return $this->json(['errors' => ['content' => 'Content must be a JSON object.']], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $targetSection->setContent($payload['content']);
        }

        if (array_key_exists('sortOrder', $payload)) {
            $targetSection->setSortOrder((int) $payload['sortOrder']);
        }

        $targetSection->setUpdatedAt(new \DateTimeImmutable());
        $page->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->json([
            'key' => $targetSection->getSectionKey(),
            'title' => $targetSection->getTitle(),
            'content' => $targetSection->getContent(),
            'sortOrder' => $targetSection->getSortOrder(),
            'updatedAt' => $targetSection->getUpdatedAt()->format(DATE_ATOM),
        ]);
    }

    #[Route('/{slug}', name: 'api_admin_pages_delete', methods: ['DELETE'])]
    public function delete(string $slug): JsonResponse
    {
        $protectedSlugs = ['home', 'contact', 'mentions-legales'];
        if (in_array($slug, $protectedSlugs, true)) {
            return $this->json(['error' => 'Cette page ne peut pas être supprimée'], Response::HTTP_FORBIDDEN);
        }

        $page = $this->pageRepository->findOneBy(['slug' => $slug]);
        if ($page === null) {
            return $this->json(['error' => 'Page non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($page);
        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }
}
