<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/services')]
final class ServiceAdminController extends AbstractController
{
    public function __construct(
        private readonly ServiceRepository $serviceRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_admin_services_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $services = $this->serviceRepository
            ->createQueryBuilder('s')
            ->orderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();

        $items = array_map(fn (Service $service): array => $this->normalizeService($service), $services);

        return $this->json(['items' => $items]);
    }

    #[Route('', name: 'api_admin_services_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->validateServicePayload($payload);
        if ($errors !== []) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $service = (new Service())
            ->setCategory(trim((string) $payload['category']))
            ->setName(trim((string) $payload['name']))
            ->setDescription(trim((string) $payload['description']))
            ->setPrices($payload['prices'])
            ->setHighlight((bool) ($payload['highlight'] ?? false))
            ->setSortOrder((int) ($payload['sortOrder'] ?? 0))
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($service);
        $this->entityManager->flush();

        return $this->json($this->normalizeService($service), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_admin_services_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $service = $this->serviceRepository->find($id);
        if (!$service instanceof Service) {
            return $this->json(['error' => 'Service not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->normalizeService($service));
    }

    #[Route('/{id}', name: 'api_admin_services_update', methods: ['PUT', 'POST'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $service = $this->serviceRepository->find($id);
        if (!$service instanceof Service) {
            return $this->json(['error' => 'Service not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->validateServicePayload($payload, false);
        if ($errors !== []) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (array_key_exists('category', $payload)) {
            $service->setCategory(trim((string) $payload['category']));
        }

        if (array_key_exists('name', $payload)) {
            $service->setName(trim((string) $payload['name']));
        }

        if (array_key_exists('description', $payload)) {
            $service->setDescription(trim((string) $payload['description']));
        }

        if (array_key_exists('prices', $payload)) {
            $service->setPrices($payload['prices']);
        }

        if (array_key_exists('highlight', $payload)) {
            $service->setHighlight((bool) $payload['highlight']);
        }

        if (array_key_exists('sortOrder', $payload)) {
            $service->setSortOrder((int) $payload['sortOrder']);
        }

        $service->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json($this->normalizeService($service));
    }

    #[Route('/{id}', name: 'api_admin_services_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $service = $this->serviceRepository->find($id);
        if (!$service instanceof Service) {
            return $this->json(['error' => 'Service not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($service);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /** @return array<string, string> */
    private function validateServicePayload(array $payload, bool $required = true): array
    {
        $errors = [];

        if (($required || array_key_exists('category', $payload)) && trim((string) ($payload['category'] ?? '')) === '') {
            $errors['category'] = 'Category is required.';
        }

        if (($required || array_key_exists('name', $payload)) && trim((string) ($payload['name'] ?? '')) === '') {
            $errors['name'] = 'Name is required.';
        }

        if (($required || array_key_exists('description', $payload)) && trim((string) ($payload['description'] ?? '')) === '') {
            $errors['description'] = 'Description is required.';
        }

        if (($required || array_key_exists('prices', $payload)) && !is_array($payload['prices'] ?? null)) {
            $errors['prices'] = 'Prices must be an array.';
        }

        return $errors;
    }

    /** @return array<string, mixed> */
    private function normalizeService(Service $service): array
    {
        return [
            'id' => $service->getId(),
            'category' => $service->getCategory(),
            'name' => $service->getName(),
            'description' => $service->getDescription(),
            'prices' => $service->getPrices(),
            'highlight' => $service->isHighlight(),
            'sortOrder' => $service->getSortOrder(),
            'createdAt' => $service->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $service->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
