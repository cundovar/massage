<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SiteSettings;
use App\Repository\SiteSettingsRepository;
use App\Service\ContactSettingsSync;
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
        private readonly ContactSettingsSync $contactSettingsSync,
    ) {
    }

    #[Route('', name: 'api_admin_settings_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $settings = $this->getOrCreateSettings();
        $this->entityManager->flush();

        return $this->json($this->normalizeSettings($settings));
    }

    #[Route('', name: 'api_admin_settings_update', methods: ['PUT', 'POST'])]
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
                $embedValue = $contact['googleMapsEmbed'] !== null ? trim((string) $contact['googleMapsEmbed']) : null;
                if ($embedValue !== null && $embedValue !== '') {
                    // Extraire l'URL si c'est un code iframe complet
                    if (preg_match('/src=["\']([^"\']+)["\']/', $embedValue, $matches)) {
                        $embedValue = $matches[1];
                    } elseif (preg_match('/^(https:\/\/www\.google\.com\/maps\/embed\?[^"\s]+)/', $embedValue, $matches)) {
                        $embedValue = $matches[1];
                    }
                }
                $settings->setGoogleMapsEmbed($embedValue);
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

            if (array_key_exists('themePreset', $payload['appearance'])) {
                $validPresets = ['ayurveda', 'spa-luxe', 'nature', 'zen', 'energique'];
                $preset = trim((string) $payload['appearance']['themePreset']);
                if (in_array($preset, $validPresets, true)) {
                    $appearance['themePreset'] = $preset;
                }
            }

            if (array_key_exists('useCustomAccent', $payload['appearance'])) {
                $appearance['useCustomAccent'] = (bool) $payload['appearance']['useCustomAccent'];
            }

            if (array_key_exists('customAccentColor', $payload['appearance'])) {
                $color = trim((string) $payload['appearance']['customAccentColor']);
                if ($color === '' || preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                    $appearance['customAccentColor'] = $color !== '' ? $color : null;
                }
            }

            if (array_key_exists('headerStyle', $payload['appearance'])) {
                $validStyles = ['transparent', 'solid', 'sticky'];
                $style = trim((string) $payload['appearance']['headerStyle']);
                if (in_array($style, $validStyles, true)) {
                    $appearance['headerStyle'] = $style;
                }
            }

            if (array_key_exists('showDarkModeToggle', $payload['appearance'])) {
                $appearance['showDarkModeToggle'] = (bool) $payload['appearance']['showDarkModeToggle'];
            }

            if (array_key_exists('bodyBackgroundImage', $payload['appearance'])) {
                $value = $payload['appearance']['bodyBackgroundImage'];
                $appearance['bodyBackgroundImage'] = ($value !== null && trim((string) $value) !== '') ? trim((string) $value) : null;
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
            if (array_key_exists('showSocialLinks', $payload['footer'])) {
                $footer['showSocialLinks'] = (bool) $payload['footer']['showSocialLinks'];
            }
            if (array_key_exists('showContactInfo', $payload['footer'])) {
                $footer['showContactInfo'] = (bool) $payload['footer']['showContactInfo'];
            }
            if (array_key_exists('showHours', $payload['footer'])) {
                $footer['showHours'] = (bool) $payload['footer']['showHours'];
            }
            if (array_key_exists('customDescription', $payload['footer'])) {
                $value = $payload['footer']['customDescription'];
                $footer['customDescription'] = ($value !== null && trim((string) $value) !== '') ? trim((string) $value) : null;
            }
            if (array_key_exists('mentionsLegalesText', $payload['footer'])) {
                $footer['mentionsLegalesText'] = trim((string) $payload['footer']['mentionsLegalesText']);
            }
            if (array_key_exists('showMentionsLegales', $payload['footer'])) {
                $footer['showMentionsLegales'] = (bool) $payload['footer']['showMentionsLegales'];
            }
            $settings->setFooterData($footer);
        }

        if (array_key_exists('navigation', $payload) && is_array($payload['navigation'])) {
            $navigation = $settings->getNavigationData() ?? [];
            if (array_key_exists('externalLinks', $payload['navigation']) && is_array($payload['navigation']['externalLinks'])) {
                $navigation['externalLinks'] = array_values(array_map(static function ($item): array {
                    if (!is_array($item)) {
                        return ['id' => '', 'label' => '', 'url' => '', 'openInNewTab' => true, 'order' => 0];
                    }
                    return [
                        'id' => trim((string) ($item['id'] ?? '')),
                        'label' => trim((string) ($item['label'] ?? '')),
                        'url' => trim((string) ($item['url'] ?? '')),
                        'openInNewTab' => (bool) ($item['openInNewTab'] ?? true),
                        'order' => (int) ($item['order'] ?? 0),
                    ];
                }, $payload['navigation']['externalLinks']));
            }
            $settings->setNavigationData($navigation);
        }

        $settings->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        // Synchroniser vers la page Contact
        $this->contactSettingsSync->syncFromSettingsToPage($settings);
        $this->contactSettingsSync->syncMapFromSettings($settings);
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
            return $this->json(['errors' => ['file' => $this->resolveUploadErrorMessage($uploaded)]], Response::HTTP_UNPROCESSABLE_ENTITY);
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
                'themePreset' => 'ayurveda',
                'useCustomAccent' => false,
                'customAccentColor' => null,
                'headerStyle' => 'sticky',
                'showDarkModeToggle' => true,
                'bodyBackgroundImage' => null,
            ])
            ->setFooterData([
                'copyrightText' => '© 2024 Helene Massage & Ayurveda',
                'quickLinks' => [],
                'showSocialLinks' => true,
                'showContactInfo' => true,
                'showHours' => false,
                'customDescription' => null,
                'mentionsLegalesText' => 'Mentions legales',
                'showMentionsLegales' => true,
            ])
            ->setNavigationData([
                'externalLinks' => [],
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
        $navigation = is_array($settings->getNavigationData()) ? $settings->getNavigationData() : [];

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
            'appearance' => $this->normalizeAppearance($appearance),
            'footer' => [
                'copyrightText' => (string) ($footer['copyrightText'] ?? ''),
                'quickLinks' => is_array($footer['quickLinks'] ?? null) ? $footer['quickLinks'] : [],
                'showSocialLinks' => (bool) ($footer['showSocialLinks'] ?? true),
                'showContactInfo' => (bool) ($footer['showContactInfo'] ?? true),
                'showHours' => (bool) ($footer['showHours'] ?? false),
                'customDescription' => $footer['customDescription'] ?? null,
                'mentionsLegalesText' => (string) ($footer['mentionsLegalesText'] ?? 'Mentions legales'),
                'showMentionsLegales' => (bool) ($footer['showMentionsLegales'] ?? true),
            ],
            'navigation' => [
                'externalLinks' => is_array($navigation['externalLinks'] ?? null) ? $navigation['externalLinks'] : [],
            ],
            'updatedAt' => $settings->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    /** @param array<string, mixed>|null $data */
    private function normalizeAppearance(?array $data): array
    {
        $data = $data ?? [];
        $validPresets = ['ayurveda', 'spa-luxe', 'nature', 'zen', 'energique'];
        $validStyles = ['transparent', 'solid', 'sticky'];
        $preset = (string) ($data['themePreset'] ?? 'ayurveda');
        $headerStyle = (string) ($data['headerStyle'] ?? 'sticky');

        return [
            'themePreset' => in_array($preset, $validPresets, true) ? $preset : 'ayurveda',
            'useCustomAccent' => (bool) ($data['useCustomAccent'] ?? false),
            'customAccentColor' => $data['customAccentColor'] ?? null,
            'headerStyle' => in_array($headerStyle, $validStyles, true) ? $headerStyle : 'sticky',
            'showDarkModeToggle' => (bool) ($data['showDarkModeToggle'] ?? true),
            'bodyBackgroundImage' => is_string($data['bodyBackgroundImage'] ?? null) && trim((string) $data['bodyBackgroundImage']) !== ''
                ? trim((string) $data['bodyBackgroundImage'])
                : null,
        ];
    }

    private function resolveUploadErrorMessage(UploadedFile $uploaded): string
    {
        return match ($uploaded->getError()) {
            UPLOAD_ERR_INI_SIZE => sprintf('File exceeds server upload limit (%s).', (string) (ini_get('upload_max_filesize') ?: 'php.ini')),
            UPLOAD_ERR_FORM_SIZE => sprintf('File exceeds form upload limit (%s).', (string) (ini_get('post_max_size') ?: 'form limit')),
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing.',
            UPLOAD_ERR_CANT_WRITE => 'Server failed to write the uploaded file.',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by a server extension.',
            default => 'Invalid upload.',
        };
    }
}
