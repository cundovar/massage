<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ReservationRequest;
use App\Repository\ReservationRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/reservation-requests')]
final class ReservationRequestAdminController extends AbstractController
{
    public function __construct(
        private readonly ReservationRequestRepository $reservationRequestRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_admin_reservation_requests_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $requests = $this->reservationRequestRepository->findBy([], ['createdAt' => 'DESC']);

        $items = array_map(fn (ReservationRequest $reservation): array => $this->normalizeReservation($reservation), $requests);

        return $this->json(['items' => $items]);
    }

    #[Route('/count-new', name: 'api_admin_reservation_requests_count_new', methods: ['GET'])]
    public function countNew(): JsonResponse
    {
        $count = $this->reservationRequestRepository->count(['status' => ReservationRequest::STATUS_NEW]);

        return $this->json(['count' => $count]);
    }

    #[Route('/{id}', name: 'api_admin_reservation_requests_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $reservation = $this->reservationRequestRepository->find($id);
        if (!$reservation instanceof ReservationRequest) {
            return $this->json(['error' => 'Reservation request not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->normalizeReservation($reservation));
    }

    #[Route('/{id}', name: 'api_admin_reservation_requests_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $reservation = $this->reservationRequestRepository->find($id);
        if (!$reservation instanceof ReservationRequest) {
            return $this->json(['error' => 'Reservation request not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        if (!array_key_exists('status', $payload)) {
            return $this->json(['errors' => ['status' => 'Status is required.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $status = (string) $payload['status'];
        $allowed = [
            ReservationRequest::STATUS_NEW,
            ReservationRequest::STATUS_READ,
            ReservationRequest::STATUS_REPLIED,
            ReservationRequest::STATUS_ARCHIVED,
        ];

        if (!in_array($status, $allowed, true)) {
            return $this->json(['errors' => ['status' => 'Invalid status.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reservation->setStatus($status);
        if ($status === ReservationRequest::STATUS_READ && $reservation->getReadAt() === null) {
            $reservation->setReadAt(new \DateTimeImmutable());
        }

        if ($status === ReservationRequest::STATUS_NEW) {
            $reservation->setReadAt(null);
        }

        $this->entityManager->flush();

        return $this->json($this->normalizeReservation($reservation));
    }

    /** @return array<string, mixed> */
    private function normalizeReservation(ReservationRequest $reservation): array
    {
        return [
            'id' => $reservation->getId(),
            'name' => $reservation->getName(),
            'email' => $reservation->getEmail(),
            'inscription' => $reservation->getInscription(),
            'phone' => $reservation->getPhone(),
            'message' => $reservation->getMessage(),
            'status' => $reservation->getStatus(),
            'service' => $reservation->getService() ? [
                'id' => $reservation->getService()->getId(),
                'name' => $reservation->getService()->getName(),
            ] : null,
            'createdAt' => $reservation->getCreatedAt()->format(DATE_ATOM),
            'readAt' => $reservation->getReadAt()?->format(DATE_ATOM),
        ];
    }
}
