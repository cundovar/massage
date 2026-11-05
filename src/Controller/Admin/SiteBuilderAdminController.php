<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Entity\SiteBuilderProject;
use App\Repository\PageRepository;
use App\Repository\SiteBuilderProjectRepository;
use App\Service\PageContentSanitizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/site-builder')]
final class SiteBuilderAdminController extends AbstractController
{
    private const MAX_PAYLOAD_BYTES = 16 * 1024 * 1024;

    public function __construct(
        private readonly SiteBuilderProjectRepository $projectRepository,
        private readonly PageRepository $pageRepository,
        private readonly PageContentSanitizer $sanitizer,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_admin_site_builder_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $project = $this->projectRepository->findMain();

        return JsonResponse::fromJsonString(json_encode([
            'project' => $project !== null ? json_decode($project->getProject(), false, 512, JSON_THROW_ON_ERROR) : null,
            'updatedAt' => $project?->getUpdatedAt()->format(DATE_ATOM),
            'pages' => array_map(fn (Page $page): array => [
                'id' => $page->getId(),
                'slug' => $page->getSlug(),
                'title' => $page->getTitle(),
                'publishedInBuilder' => $page->isPublishedInBuilder(),
            ], $this->pageRepository->findBy([], ['id' => 'ASC'])),
        ], JSON_THROW_ON_ERROR));
    }

    #[Route('', name: 'api_admin_site_builder_update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        if (strlen($request->getContent()) > self::MAX_PAYLOAD_BYTES) {
            return $this->json(['error' => 'Le projet est trop volumineux.'], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        try {
            $payload = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$payload instanceof \stdClass || !($payload->project ?? null) instanceof \stdClass) {
            return $this->json(['error' => 'Le champ project est obligatoire.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $exports = $payload->pages ?? new \stdClass();
        if (!$exports instanceof \stdClass) {
            return $this->json(['error' => 'Le champ pages est invalide.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pages = [];
        foreach (get_object_vars($exports) as $pageId => $export) {
            $page = $this->pageRepository->find((int) $pageId);
            if ($page === null) {
                return $this->json(['error' => sprintf('Page %s introuvable.', $pageId)], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (!is_string($export->html ?? null) || !is_string($export->css ?? null)) {
                return $this->json(['error' => sprintf('Export invalide pour la page "%s".', $page->getSlug())], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (!$this->sanitizer->isCssAllowed($export->css)) {
                return $this->json(['error' => sprintf('CSS non autorisé sur la page "%s".', $page->getSlug())], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $pages[] = [$page, $this->sanitizer->sanitizeHtml($export->html), $export->css];
        }

        $projectJson = json_encode($payload->project, JSON_THROW_ON_ERROR);
        $expectedUpdatedAt = is_string($payload->updatedAt ?? null) ? $payload->updatedAt : null;

        $project = $this->entityManager->wrapInTransaction(function () use ($projectJson, $expectedUpdatedAt, $pages): ?SiteBuilderProject {
            $project = $this->projectRepository->findMainForUpdate();

            if ($project?->getUpdatedAt()->format(DATE_ATOM) !== $expectedUpdatedAt) {
                return null;
            }

            if ($project === null) {
                $project = new SiteBuilderProject($projectJson);
                $this->entityManager->persist($project);
            } else {
                $project->replaceProject($projectJson);
            }

            foreach ($pages as [$page, $html, $css]) {
                $page->setHtml($html)->setCss($css)->setUpdatedAt(new \DateTimeImmutable());
            }

            return $project;
        });

        if ($project === null) {
            return $this->json(
                ['error' => 'Le site a été modifié ailleurs entre-temps. Rechargez avant d\'enregistrer.'],
                Response::HTTP_CONFLICT
            );
        }

        return $this->json([
            'updatedAt' => $project->getUpdatedAt()->format(DATE_ATOM),
            'slugs' => array_map(static fn (array $entry): string => $entry[0]->getSlug(), $pages),
        ]);
    }
}
