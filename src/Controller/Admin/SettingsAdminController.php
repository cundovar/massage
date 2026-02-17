<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SiteSettings;
use App\Repository\SiteSettingsRepository;
use App\Service\MediaUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/settings')]
final class SettingsAdminController extends AbstractController
{
    private const MAX_FILE_SIZE_BYTES = 2_097_152;

    public function __construct(
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaUploader $mediaUploader,
    ) {
    }

    #[Route('', name: 'api_admin_settings_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $settings = $this->getOrCreateSettings();
        $this->entityManager->flush();

        return $this->json($this->normalizeSettings($settings));
    }

    #[Route('', name: 'api_admin_settings_update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $settings = $this->getOrCreateSettings();

        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('general', $payload) && is_array($payload['general'])) {
            $general = $payload['general'];
            if (array_key_exists('siteName', $general)) {
                $siteName = trim((string) $general['siteName']);
                if ($siteName === '') {
                    return $this->json(['errors' => ['general.siteName' => 'Site name cannot be empty.']], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $settings->setSiteName($siteName);
            }
            if (array_key_exists('logo', $general)) {
                $settings->setLogo($general['logo'] !== null ? trim((string) $general['logo']) : null);
            }
            if (array_key_exists('favicon', $general)) {
                $settings->setFavicon($general['favicon'] !== null ? trim((string) $general['favicon']) : null);
            }
            if (array_key_exists('defaultMetaDescription', $general)) {
                $settings->setDefaultMetaDescription($general['defaultMetaDescription'] !== null ? trim((string) $general['defaultMetaDescription']) : null);
            }
        }

        if (array_key_exists('contact', $payload) && is_array($payload['contact'])) {
            $contact = $payload['contact'];

            if (array_key_exists('email', $contact)) {
                $email = trim((string) $contact['email']);
                if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    return $this->json(['errors' => ['contact.email' => 'Invalid email format.']], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $settings->setContactEmail($email);
            }

            if (array_key_exists('phone', $contact)) {
                $settings->setContactPhone($contact['phone'] !== null ? trim((string) $contact['phone']) : null);
            }

            if (array_key_exists('address', $contact)) {
                if (!is_array($contact['address'])) {
                    return $this->json(['errors' => ['contact.address' => 'Address must be an object.']], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $address = $settings->getAddress() ?? [];
                $address['street'] = isset($contact['address']['street']) ? trim((string) $contact['address']['street']) : ($address['street'] ?? '');
                $address['postalCode'] = isset($contact['address']['postalCode']) ? trim((string) $contact['address']['postalCode']) : ($address['postalCode'] ?? '');
                $address['city'] = isset($contact['address']['city']) ? trim((string) $contact['address']['city']) : ($address['city'] ?? '');
                $settings->setAddress($address);
            }

            if (array_key_exists('googleMapsUrl', $contact)) {
                $settings->setGoogleMapsUrl($contact['googleMapsUrl'] !== null ? trim((string) $contact['googleMapsUrl']) : null);
            }
            if (array_key_exists('googleMapsEmbed', $contact)) {
                $settings->setGoogleMapsEmbed($contact['googleMapsEmbed'] !== null ? trim((string) $contact['googleMapsEmbed']) : null);
            }
        }

        if (array_key_exists('hours', $payload) && is_array($payload['hours'])) {
            $hoursData = $settings->getHoursData() ?? [];
            if (array_key_exists('schedule', $payload['hours']) && is_array($payload['hours']['schedule'])) {
                $hoursData['schedule'] = array_values(array_map(static function ($item): array {
                    if (!is_array($item)) {
                        return ['days' => '', 'hours' => ''];
                    }
                    return [
                        'days' => trim((string) ($item['days'] ?? '')),
                        'hours' => trim((string) ($item['hours'] ?? '')),
                    ];
                }, $payload['hours']['schedule']));
            }
            if (array_key_exists('closedMessage', $payload['hours'])) {
                $hoursData['closedMessage'] = trim((string) $payload['hours']['closedMessage']);
            }
            $settings->setHoursData($hoursData);
        }

        if (array_key_exists('social', $payload) && is_array($payload['social'])) {
            $socialLinks = $settings->getSocialLinks() ?? [];
            foreach (['instagram', 'facebook', 'linkedin'] as $key) {
                if (array_key_exists($key, $payload['social'])) {
                    $value = $payload['social'][$key];
                    $socialLinks[$key] = $value !== null ? trim((string) $value) : null;
                }
            }
            $settings->setSocialLinks($socialLinks);
        }

        if (array_key_exists('booking', $payload) && is_array($payload['booking'])) {
            $bookingData = $settings->getBookingData() ?? [];
            if (array_key_exists('notificationEmail', $payload['booking'])) {
                $email = trim((string) $payload['booking']['notificationEmail']);
                if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    return $this->json(['errors' => ['booking.notificationEmail' => 'Invalid email format.']], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $bookingData['notificationEmail'] = $email;
            }
            if (array_key_exists('minDelayHours', $payload['booking'])) {
                $bookingData['minDelayHours'] = max(0, (int) $payload['booking']['minDelayHours']);
            }
            if (array_key_exists('confirmationMessage', $payload['booking'])) {
                $bookingData['confirmationMessage'] = trim((string) $payload['booking']['confirmationMessage']);
            }
            $settings->setBookingData($bookingData);
        }

        if (array_key_exists('appearance', $payload) && is_array($payload['appearance'])) {
            $appearance = $settings->getAppearanceData() ?? [];
            if (array_key_exists('primaryColor', $payload['appearance'])) {
                $appearance['primaryColor'] = trim((string) $payload['appearance']['primaryColor']);
            }
            if (array_key_exists('darkModeDefault', $payload['appearance'])) {
                $appearance['darkModeDefault'] = (bool) $payload['appearance']['darkModeDefault'];
            }
            $settings->setAppearanceData($appearance);
        }

        if (array_key_exists('footer', $payload) && is_array($payload['footer'])) {
            $footer = $settings->getFooterData() ?? [];
            if (array_key_exists('copyrightText', $payload['footer'])) {
                $footer['copyrightText'] = trim((string) $payload['footer']['copyrightText']);
            }
            if (array_key_exists('quickLinks', $payload['footer']) && is_array($payload['footer']['quickLinks'])) {
                $footer['quickLinks'] = array_values(array_map(static function ($item): array {
                    if (!is_array($item)) {
                        return ['label' => '', 'url' => ''];
                    }
                    return [
                        'label' => trim((string) ($item['label'] ?? '')),
                        'url' => trim((string) ($item['url'] ?? '')),
                    ];
                }, $payload['footer']['quickLinks']));
            }
            $settings->setFooterData($footer);
        }

        $settings->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json($this->normalizeSettings($settings));
    }

    #[Route('/logo', name: 'api_admin_settings_upload_logo', methods: ['POST'])]
    public function uploadLogo(Request $request): JsonResponse
    {
        return $this->handleAssetUpload($request, 'logo');
    }

    #[Route('/favicon', name: 'api_admin_settings_upload_favicon', methods: ['POST'])]
    public function uploadFavicon(Request $request): JsonResponse
    {
        return $this->handleAssetUpload($request, 'favicon');
    }

    private function handleAssetUpload(Request $request, string $kind): JsonResponse
    {
        $uploaded = $request->files->get('file');
        if (!$uploaded instanceof UploadedFile) {
            return $this->json(['errors' => ['file' => 'File is required.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$uploaded->isValid()) {
            return $this->json(['errors' => ['file' => 'Invalid upload.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($uploaded->getSize() > self::MAX_FILE_SIZE_BYTES) {
            return $this->json(['errors' => ['file' => 'File exceeds 2MB limit.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $fileData = $this->mediaUploader->upload($uploaded);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['errors' => ['file' => $exception->getMessage()]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $path = '/images/' . $fileData['filename'];
        $settings = $this->getOrCreateSettings();
        if ($kind === 'favicon') {
            $settings->setFavicon($path);
        } else {
            $settings->setLogo($path);
        }
        $settings->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $this->json(['path' => $path]);
    }

    private function getOrCreateSettings(): SiteSettings
    {
        $settings = $this->siteSettingsRepository->findMain();
        if ($settings instanceof SiteSettings) {
            return $settings;
        }

        $settings = (new SiteSettings())
            ->setId(1)
            ->setSiteName('Helene Massage & Ayurveda')
            ->setTagline('Massages ayurvediques, reflexologie et Kobido a Paris.')
            ->setDefaultMetaDescription('Massages ayurvediques, reflexologie et Kobido a Paris.')
            ->setContactEmail('contact@helene-massage.fr')
            ->setContactPhone('06 12 34 56 78')
            ->setAddress([
                'street' => '123 Rue du Bien-Etre',
                'postalCode' => '75011',
                'city' => 'Paris',
            ])
            ->setSocialLinks([
                'instagram' => null,
                'facebook' => null,
                'linkedin' => null,
            ])
            ->setHoursData([
                'schedule' => [
                    ['days' => 'Lundi - Vendredi', 'hours' => '10h - 20h'],
                    ['days' => 'Samedi', 'hours' => '10h - 18h'],
                ],
                'closedMessage' => 'Ferme le dimanche',
            ])
            ->setBookingData([
                'notificationEmail' => 'contact@helene-massage.fr',
                'minDelayHours' => 24,
                'confirmationMessage' => 'Merci pour votre demande. Je vous recontacte dans les 24h.',
            ])
            ->setAppearanceData([
                'primaryColor' => '#D4A574',
                'darkModeDefault' => false,
            ])
            ->setFooterData([
                'copyrightText' => '© 2024 Helene Massage & Ayurveda',
                'quickLinks' => [],
            ])
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($settings);

        return $settings;
    }

    /** @return array<string, mixed> */
    private function normalizeSettings(SiteSettings $settings): array
    {
        $address = is_array($settings->getAddress()) ? $settings->getAddress() : [];
        $social = is_array($settings->getSocialLinks()) ? $settings->getSocialLinks() : [];
        $hours = is_array($settings->getHoursData()) ? $settings->getHoursData() : [];
        $booking = is_array($settings->getBookingData()) ? $settings->getBookingData() : [];
        $appearance = is_array($settings->getAppearanceData()) ? $settings->getAppearanceData() : [];
        $footer = is_array($settings->getFooterData()) ? $settings->getFooterData() : [];

        return [
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
                'notificationEmail' => (string) ($booking['notificationEmail'] ?? $settings->getContactEmail()),
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
            'updatedAt' => $settings->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
