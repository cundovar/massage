<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Page;
use App\Repository\PageRepository;
use App\Repository\SiteSettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/navigation')]
final class NavigationController extends AbstractController
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly SiteSettingsRepository $siteSettingsRepository,
    ) {
    }

    #[Route('', name: 'api_navigation_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $pages = $this->pageRepository->findBy(
            ['showInNav' => true],
            ['navOrder' => 'ASC', 'id' => 'ASC']
        );

        $pageItems = array_map(fn (Page $page): array => [
            'slug' => $page->getSlug(),
            'title' => $page->getNavTitle() ?? $page->getTitle(),
            'path' => $this->pathFromSlug($page->getSlug()),
            'order' => $page->getNavOrder(),
            'isExternal' => false,
        ], $pages);

        // Get external links from settings
        $externalItems = [];
        $settings = $this->siteSettingsRepository->findMain();
        if ($settings !== null) {
            $navigationData = $settings->getNavigationData();
            if (is_array($navigationData) && isset($navigationData['externalLinks']) && is_array($navigationData['externalLinks'])) {
                foreach ($navigationData['externalLinks'] as $link) {
                    if (!is_array($link)) {
                        continue;
                    }
                    $externalItems[] = [
                        'slug' => 'external-' . ($link['id'] ?? uniqid()),
                        'title' => $link['label'] ?? '',
                        'path' => $link['url'] ?? '',
                        'order' => (int) ($link['order'] ?? 999),
                        'isExternal' => true,
                        'openInNewTab' => (bool) ($link['openInNewTab'] ?? true),
                    ];
                }
            }
        }

        // Merge and sort all items by order
        $allItems = array_merge($pageItems, $externalItems);
        usort($allItems, static fn (array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        // Remove internal 'order' field from output
        $items = array_map(static function (array $item): array {
            unset($item['order']);
            return $item;
        }, $allItems);

        if ($items === []) {
            $items = [
                ['slug' => 'home', 'title' => 'Accueil', 'path' => '/', 'isExternal' => false],
                ['slug' => 'soins', 'title' => 'Carte & tarifs', 'path' => '/soins', 'isExternal' => false],
                ['slug' => 'entreprise', 'title' => 'Entreprise', 'path' => '/entreprise', 'isExternal' => false],
                ['slug' => 'about', 'title' => 'À propos', 'path' => '/a-propos', 'isExternal' => false],
                ['slug' => 'contact', 'title' => 'Contact', 'path' => '/contact', 'isExternal' => false],
            ];
        }

        return $this->json(['items' => $items]);
    }

    private function pathFromSlug(string $slug): string
    {
        return match ($slug) {
            'home' => '/',
            'about' => '/a-propos',
            default => '/' . $slug,
        };
    }
}
