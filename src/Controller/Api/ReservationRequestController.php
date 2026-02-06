<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\ReservationRequest;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reservation-requests')]
final class ReservationRequestController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServiceRepository $serviceRepository,
    ) {
    }

    #[Route('', name: 'api_reservation_requests_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $inscription = isset($payload['inscription']) ? trim((string) $payload['inscription']) : null;
        $phone = isset($payload['phone']) ? trim((string) $payload['phone']) : null;
        $message = trim((string) ($payload['message'] ?? ''));
        $serviceId = $payload['serviceId'] ?? null;

        $errors = [];
        if ($name == '') {
            $errors['name'] = 'Name is required.';
        }

        if ($email == '') {
            $errors['email'] = 'Email is required.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email format is invalid.';
        }

        if ($message == '') {
            $errors['message'] = 'Message is required.';
        }

        $service = null;
        if ($serviceId !== null && $serviceId !== '') {
            $service = $this->serviceRepository->find((int) $serviceId);
            if ($service === null) {
                $errors['serviceId'] = 'Service not found.';
            }
        }

        if ($errors !== []) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reservationRequest = (new ReservationRequest())
            ->setName($name)
            ->setEmail($email)
            ->setInscription($inscription !== '' ? $inscription : null)
            ->setPhone($phone)
            ->setMessage($message)
            ->setStatus(ReservationRequest::STATUS_NEW)
            ->setService($service);

        $this->entityManager->persist($reservationRequest);
        $this->entityManager->flush();

        return $this->json([
            'id' => $reservationRequest->getId(),
            'status' => $reservationRequest->getStatus(),
            'createdAt' => $reservationRequest->getCreatedAt()->format(DATE_ATOM),
        ], Response::HTTP_CREATED);
    }
}
