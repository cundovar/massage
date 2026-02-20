<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PageSection;
use App\Entity\SiteSettings;
use App\Repository\PageRepository;
use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de synchronisation bidirectionnelle entre Settings.contact et la section "infos" de la page Contact.
 */
final class ContactSettingsSync
{
    /**
     * Extrait l'URL src d'un code iframe ou retourne l'URL telle quelle si c'est déjà une URL.
     */
    private function extractEmbedUrl(string $input): string
    {
        $input = trim($input);

        // Si c'est déjà une URL propre
        if (str_starts_with($input, 'https://www.google.com/maps/embed')) {
            // Enlever tout ce qui vient après les guillemets (attributs HTML parasites)
            if (preg_match('/^(https:\/\/www\.google\.com\/maps\/embed\?[^"\s]+)/', $input, $matches)) {
                return $matches[1];
            }
            return $input;
        }

        // Si c'est un code iframe, extraire le src
        if (preg_match('/src=["\']([^"\']+)["\']/', $input, $matches)) {
            return $matches[1];
        }

        return $input;
    }

    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Synchronise les données de Settings vers la section "infos" de la page Contact.
     */
    public function syncFromSettingsToPage(SiteSettings $settings): void
    {
        $contactPage = $this->pageRepository->findOneBy(['slug' => 'contact']);
        if ($contactPage === null) {
            return;
        }

        $infosSection = null;
        foreach ($contactPage->getSections() as $section) {
            if ($section->getType() === 'contact-infos') {
                $infosSection = $section;
                break;
            }
        }

        if ($infosSection === null) {
            return;
        }

        $address = $settings->getAddress() ?? [];
        $hoursData = $settings->getHoursData() ?? [];

        $content = $infosSection->getContent() ?? [];
        $content['address'] = [
            'street' => (string) ($address['street'] ?? ''),
            'city' => trim(($address['postalCode'] ?? '') . ' ' . ($address['city'] ?? '')),
        ];
        $content['phone'] = $settings->getContactPhone() ?? '';
        $content['email'] = $settings->getContactEmail();
        $content['hours'] = $hoursData['schedule'] ?? [];

        $infosSection->setContent($content);
        $infosSection->setUpdatedAt(new \DateTimeImmutable());
        $contactPage->setUpdatedAt(new \DateTimeImmutable());
    }

    /**
     * Synchronise les données de la section "infos" vers Settings.
     */
    public function syncFromPageToSettings(PageSection $infosSection): void
    {
        $settings = $this->siteSettingsRepository->findMain();
        if ($settings === null) {
            return;
        }

        $content = $infosSection->getContent() ?? [];

        // Sync address
        if (isset($content['address']) && is_array($content['address'])) {
            $address = $settings->getAddress() ?? [];

            if (isset($content['address']['street'])) {
                $address['street'] = (string) $content['address']['street'];
            }

            // Parse city field (format: "75020 Paris")
            if (isset($content['address']['city'])) {
                $cityParts = explode(' ', trim((string) $content['address']['city']), 2);
                if (count($cityParts) === 2 && preg_match('/^\d{5}$/', $cityParts[0])) {
                    $address['postalCode'] = $cityParts[0];
                    $address['city'] = $cityParts[1];
                } else {
                    $address['city'] = (string) $content['address']['city'];
                }
            }

            $settings->setAddress($address);
        }

        // Sync phone
        if (isset($content['phone'])) {
            $settings->setContactPhone((string) $content['phone']);
        }

        // Sync email
        if (isset($content['email'])) {
            $email = trim((string) $content['email']);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                $settings->setContactEmail($email);
            }
        }

        // Sync hours
        if (isset($content['hours']) && is_array($content['hours'])) {
            $hoursData = $settings->getHoursData() ?? [];
            $hoursData['schedule'] = array_values(array_map(static function ($item): array {
                if (!is_array($item)) {
                    return ['days' => '', 'hours' => ''];
                }
                return [
                    'days' => trim((string) ($item['days'] ?? '')),
                    'hours' => trim((string) ($item['hours'] ?? '')),
                ];
            }, $content['hours']));
            $settings->setHoursData($hoursData);
        }

        $settings->setUpdatedAt(new \DateTimeImmutable());
    }

    /**
     * Synchronise l'URL Google Maps Embed de Settings vers la section "map" de la page Contact.
     */
    public function syncMapFromSettings(SiteSettings $settings): void
    {
        $contactPage = $this->pageRepository->findOneBy(['slug' => 'contact']);
        if ($contactPage === null) {
            return;
        }

        $mapSection = null;
        foreach ($contactPage->getSections() as $section) {
            if ($section->getSectionKey() === 'map' || $section->getType() === 'google-map') {
                $mapSection = $section;
                break;
            }
        }

        if ($mapSection === null) {
            return;
        }

        $embedUrl = $settings->getGoogleMapsEmbed();
        if ($embedUrl !== null && $embedUrl !== '') {
            $content = $mapSection->getContent() ?? [];
            $content['embedUrl'] = $embedUrl;
            $mapSection->setContent($content);
            $mapSection->setUpdatedAt(new \DateTimeImmutable());
            $contactPage->setUpdatedAt(new \DateTimeImmutable());
        }
    }

    /**
     * Synchronise l'URL Google Maps de la section "map" vers Settings.
     */
    public function syncMapToSettings(PageSection $mapSection): void
    {
        $settings = $this->siteSettingsRepository->findMain();
        if ($settings === null) {
            return;
        }

        $content = $mapSection->getContent() ?? [];
        if (isset($content['embedUrl']) && is_string($content['embedUrl'])) {
            $embedUrl = $this->extractEmbedUrl($content['embedUrl']);
            if ($embedUrl !== '' && str_contains($embedUrl, 'google.com/maps/embed')) {
                $settings->setGoogleMapsEmbed($embedUrl);
                $settings->setUpdatedAt(new \DateTimeImmutable());
            }
        }
    }
}
