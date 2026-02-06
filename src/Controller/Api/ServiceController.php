<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/services')]
final class ServiceController extends AbstractController
{
    public function __construct(private readonly ServiceRepository $serviceRepository)
    {
    }

    #[Route('', name: 'api_services_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $services = $this->serviceRepository
            ->createQueryBuilder('s')
            ->orderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();

        $items = array_map(static fn ($service): array => [
            'id' => $service->getId(),
            'category' => $service->getCategory(),
            'name' => $service->getName(),
            'description' => $service->getDescription(),
            'prices' => $service->getPrices(),
            'highlight' => $service->isHighlight(),
            'sortOrder' => $service->getSortOrder(),
        ], $services);

        return $this->json(['items' => $items]);
    }
}
