<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\SiteSettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $settings = $this->siteSettingsRepository->findMain();
        if ($settings === null) {
            return $this->json([
                'general' => [
                    'siteName' => 'Helene Massage & Ayurveda',
                    'logo' => null,
                    'favicon' => null,
                    'defaultMetaDescription' => 'Massages ayurvediques, reflexologie et Kobido a Paris.',
                ],
                'contact' => [
                    'address' => ['street' => '', 'postalCode' => '', 'city' => ''],
                    'phone' => '',
                    'email' => '',
                    'googleMapsUrl' => null,
                    'googleMapsEmbed' => null,
                ],
                'hours' => ['schedule' => [], 'closedMessage' => ''],
                'social' => ['instagram' => null, 'facebook' => null, 'linkedin' => null],
                'booking' => ['minDelayHours' => 24, 'confirmationMessage' => ''],
                'appearance' => ['primaryColor' => '#D4A574', 'darkModeDefault' => false],
                'footer' => ['copyrightText' => '', 'quickLinks' => []],
            ]);
        }

        $address = is_array($settings->getAddress()) ? $settings->getAddress() : [];
        $social = is_array($settings->getSocialLinks()) ? $settings->getSocialLinks() : [];
        $hours = is_array($settings->getHoursData()) ? $settings->getHoursData() : [];
        $booking = is_array($settings->getBookingData()) ? $settings->getBookingData() : [];
        $appearance = is_array($settings->getAppearanceData()) ? $settings->getAppearanceData() : [];
        $footer = is_array($settings->getFooterData()) ? $settings->getFooterData() : [];

        return $this->json([
            'general' => [
                'siteName' => $settings->getSiteName(),
                'logo' => $settings->getLogo(),
                'favicon' => $settings->getFavicon(),
                'defaultMetaDescription' => $settings->getDefaultMetaDescription() ?? $settings->getTagline() ?? '',
            ],
            'contact' => [
                'address' => [
                    'street' => (string) ($address['street'] ?? ''),
                    'postalCode' => (string) ($address['postalCode'] ?? ''),
                    'city' => (string) ($address['city'] ?? ''),
                ],
                'phone' => $settings->getContactPhone() ?? '',
                'email' => $settings->getContactEmail(),
                'googleMapsUrl' => $settings->getGoogleMapsUrl(),
                'googleMapsEmbed' => $settings->getGoogleMapsEmbed(),
            ],
            'hours' => [
                'schedule' => is_array($hours['schedule'] ?? null) ? $hours['schedule'] : [],
                'closedMessage' => (string) ($hours['closedMessage'] ?? ''),
            ],
            'social' => [
                'instagram' => $social['instagram'] ?? null,
                'facebook' => $social['facebook'] ?? null,
                'linkedin' => $social['linkedin'] ?? null,
            ],
            'booking' => [
                'minDelayHours' => (int) ($booking['minDelayHours'] ?? 24),
                'confirmationMessage' => (string) ($booking['confirmationMessage'] ?? ''),
            ],
            'appearance' => [
                'primaryColor' => (string) ($appearance['primaryColor'] ?? '#D4A574'),
                'darkModeDefault' => (bool) ($appearance['darkModeDefault'] ?? false),
            ],
            'footer' => [
                'copyrightText' => (string) ($footer['copyrightText'] ?? ''),
                'quickLinks' => is_array($footer['quickLinks'] ?? null) ? $footer['quickLinks'] : [],
            ],
        ]);
    }
}
