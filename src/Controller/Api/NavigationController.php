<?php

declare(strict_types=1);

namespace App\Controller\Api;

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
        $pages = $this->pageRepository->findBy([], ['id' => 'ASC']);

        $bySlug = [];
        foreach ($pages as $page) {
            $bySlug[$page->getSlug()] = $page;
        }

        $items = [];
        foreach (['home', 'soins', 'about', 'contact'] as $slug) {
            if (!isset($bySlug[$slug])) {
                continue;
            }

            $items[] = [
                'slug' => $slug,
                'title' => $bySlug[$slug]->getTitle(),
                'path' => $this->pathFromSlug($slug),
            ];
        }

        if ($items === []) {
            $items = [
                ['slug' => 'home', 'title' => 'Accueil', 'path' => '/'],
                ['slug' => 'soins', 'title' => 'Soins', 'path' => '/soins'],
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
