<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SiteSettings;
use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/settings')]
final class SettingsAdminController extends AbstractController
{
    public function __construct(
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_admin_settings_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $settings = $this->siteSettingsRepository->find(1) ?? $this->siteSettingsRepository->findOneBy([]);
        if (!$settings instanceof SiteSettings) {
            return $this->json(['error' => 'Settings not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->normalizeSettings($settings));
    }

    #[Route('', name: 'api_admin_settings_update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $settings = $this->siteSettingsRepository->find(1) ?? $this->siteSettingsRepository->findOneBy([]);
        if (!$settings instanceof SiteSettings) {
            $settings = (new SiteSettings())->setId(1);
            $this->entityManager->persist($settings);
        }

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $required = ['siteName', 'contactEmail'];
        foreach ($required as $field) {
            if (array_key_exists($field, $payload) && trim((string) $payload[$field]) === '') {
                return $this->json(['errors' => [$field => sprintf('%s cannot be empty.', $field)]], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        if (array_key_exists('siteName', $payload)) {
            $settings->setSiteName(trim((string) $payload['siteName']));
        }

        if (array_key_exists('tagline', $payload)) {
            $settings->setTagline($payload['tagline'] !== null ? trim((string) $payload['tagline']) : null);
        }

        if (array_key_exists('logo', $payload)) {
            $settings->setLogo($payload['logo'] !== null ? trim((string) $payload['logo']) : null);
        }

        if (array_key_exists('contactEmail', $payload)) {
            $email = trim((string) $payload['contactEmail']);
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return $this->json(['errors' => ['contactEmail' => 'Invalid email format.']], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $settings->setContactEmail($email);
        }

        if (array_key_exists('contactPhone', $payload)) {
            $settings->setContactPhone($payload['contactPhone'] !== null ? trim((string) $payload['contactPhone']) : null);
        }

        if (array_key_exists('address', $payload)) {
            if ($payload['address'] !== null && !is_array($payload['address'])) {
                return $this->json(['errors' => ['address' => 'Address must be an object or null.']], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $settings->setAddress($payload['address']);
        }

        if (array_key_exists('socialLinks', $payload)) {
            if ($payload['socialLinks'] !== null && !is_array($payload['socialLinks'])) {
                return $this->json(['errors' => ['socialLinks' => 'Social links must be an object or null.']], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $settings->setSocialLinks($payload['socialLinks']);
        }

        $settings->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->json($this->normalizeSettings($settings));
    }

    /** @return array<string, mixed> */
    private function normalizeSettings(SiteSettings $settings): array
    {
        return [
            'id' => $settings->getId(),
            'siteName' => $settings->getSiteName(),
            'tagline' => $settings->getTagline(),
            'logo' => $settings->getLogo(),
            'contactEmail' => $settings->getContactEmail(),
            'contactPhone' => $settings->getContactPhone(),
            'address' => $settings->getAddress(),
            'socialLinks' => $settings->getSocialLinks(),
            'updatedAt' => $settings->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
