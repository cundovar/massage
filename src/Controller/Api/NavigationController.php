<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Page;
use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/navigation')]
final class NavigationController extends AbstractController
{
    public function __construct(private readonly PageRepository $pageRepository)
    {
    }

    #[Route('', name: 'api_navigation_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $pages = $this->pageRepository->findBy(
            ['showInNav' => true],
            ['navOrder' => 'ASC', 'id' => 'ASC']
        );

        $items = array_map(fn (Page $page): array => [
            'slug' => $page->getSlug(),
            'title' => $page->getNavTitle() ?? $page->getTitle(),
            'path' => $this->pathFromSlug($page->getSlug()),
        ], $pages);

        if ($items === []) {
            $items = [
                ['slug' => 'home', 'title' => 'Accueil', 'path' => '/'],
                ['slug' => 'soins', 'title' => 'Carte & tarifs', 'path' => '/soins'],
                ['slug' => 'entreprise', 'title' => 'Entreprise', 'path' => '/entreprise'],
                ['slug' => 'about', 'title' => 'À propos', 'path' => '/a-propos'],
                ['slug' => 'contact', 'title' => 'Contact', 'path' => '/contact'],
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
