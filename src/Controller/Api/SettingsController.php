<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\SiteSettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/settings')]
final class SettingsController extends AbstractController
{
    public function __construct(private readonly SiteSettingsRepository $siteSettingsRepository)
    {
    }

    #[Route('', name: 'api_settings_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $settings = $this->siteSettingsRepository->find(1) ?? $this->siteSettingsRepository->findOneBy([]);

        if ($settings === null) {
            return $this->json(['error' => 'Settings not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'siteName' => $settings->getSiteName(),
            'tagline' => $settings->getTagline(),
            'logo' => $settings->getLogo(),
            'contactEmail' => $settings->getContactEmail(),
            'contactPhone' => $settings->getContactPhone(),
            'address' => $settings->getAddress(),
            'socialLinks' => $settings->getSocialLinks(),
        ]);
    }
}
