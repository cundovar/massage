<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\SiteSettingsRepository;
use App\Service\AppearanceNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/settings')]
final class SettingsController extends AbstractController
{
    public function __construct(
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly AppearanceNormalizer $appearanceNormalizer,
    ) {
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
                    'locations' => [],
                    'phone' => '',
                    'email' => '',
                    'googleMapsUrl' => null,
                    'googleMapsEmbed' => null,
                ],
                'hours' => ['schedule' => [], 'closedMessage' => ''],
                'social' => ['instagram' => null, 'facebook' => null, 'linkedin' => null],
                'booking' => ['minDelayHours' => 24, 'confirmationMessage' => ''],
                'appearance' => $this->appearanceNormalizer->normalize(null),
                'footer' => [
                    'copyrightText' => '',
                    'quickLinks' => [],
                    'showSocialLinks' => true,
                    'showContactInfo' => true,
                    'addressDisplay' => 'all',
                    'selectedAddressIndex' => 0,
                    'addressSummary' => 'Deux lieux pour les massages',
                    'showHours' => false,
                    'customDescription' => null,
                    'mentionsLegalesText' => 'Mentions legales',
                    'showMentionsLegales' => true,
                    'style' => 'light',
                    'backgroundColor' => '',
                ],
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
                'locations' => is_array($address['locations'] ?? null)
                    ? $address['locations']
                    : [[
                        'label' => 'Lieu principal',
                        'street' => (string) ($address['street'] ?? ''),
                        'postalCode' => (string) ($address['postalCode'] ?? ''),
                        'city' => (string) ($address['city'] ?? ''),
                    ]],
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
            'appearance' => $this->appearanceNormalizer->normalize($appearance),
            'footer' => [
                'copyrightText' => (string) ($footer['copyrightText'] ?? ''),
                'quickLinks' => is_array($footer['quickLinks'] ?? null) ? $footer['quickLinks'] : [],
                'showSocialLinks' => (bool) ($footer['showSocialLinks'] ?? true),
                'showContactInfo' => (bool) ($footer['showContactInfo'] ?? true),
                'addressDisplay' => (string) ($footer['addressDisplay'] ?? 'all'),
                'selectedAddressIndex' => (int) ($footer['selectedAddressIndex'] ?? 0),
                'addressSummary' => (string) ($footer['addressSummary'] ?? 'Deux lieux pour les massages'),
                'showHours' => (bool) ($footer['showHours'] ?? false),
                'customDescription' => $footer['customDescription'] ?? null,
                'mentionsLegalesText' => (string) ($footer['mentionsLegalesText'] ?? 'Mentions legales'),
                'showMentionsLegales' => (bool) ($footer['showMentionsLegales'] ?? true),
                'style' => (string) ($footer['style'] ?? 'light'),
                'backgroundColor' => (string) ($footer['backgroundColor'] ?? ''),
            ],
        ]);
    }
}
