<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Page;
use App\Entity\PageSection;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class PageFixtures extends Fixture
{
    private const SECTION_TYPE_MAP = [
        'hero' => 'hero',
        'presentation' => 'presentation',
        'approche' => 'approche',
        'tarifs' => 'tarifs',
        'entreprise' => 'entreprise',
        'intro' => 'text',
        'parcours' => 'text',
        'formations' => 'text',
        'infos' => 'text',
        'content' => 'text',
        'philosophie' => 'quote',
    ];

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $this->createSystemPage(
            $manager,
            'home',
            'Accueil',
            0,
            $now,
            [
                ['hero', 'Hero', ['siteTitle' => 'Helene - Massages & Ayurveda', 'slides' => [['image' => '/images/default/hero-1.jpg', 'title' => 'Une pause pour vous recentrer', 'subtitle' => 'Massages ayurvediques']]]],
                ['presentation', 'Présentation', ['title' => 'Présentation', 'image' => null, 'paragraphs' => ['Je vous accueille dans un cadre calme et chaleureux.'], 'quote' => '']],
                ['approche', 'Approche', ['title' => 'Approche', 'images' => [], 'bulletsTitle' => 'Ce qui guide mes mains :', 'bullets' => ['Un entretien préalable.', 'Une écoute précise du corps.'], 'quote' => '']],
            ]
        );

        $this->createSystemPage(
            $manager,
            'soins',
            'Carte & tarifs',
            1,
            $now,
            [
                ['hero', 'Hero', ['title' => 'Soins & Massages', 'image' => null]],
                ['intro', 'Introduction', ['title' => '', 'paragraphs' => ['Chaque soin est pensé comme un moment unique.'], 'image' => null]],
                ['tarifs', 'Tarifs', ['title' => 'Carte & tarifs', 'subtitle' => '', 'offers' => []]],
            ]
        );

        $this->createSystemPage(
            $manager,
            'entreprise',
            'Entreprise',
            2,
            $now,
            [
                ['entreprise', 'Entreprise', [
                    'title' => 'Massage Amma en entreprise',
                    'subtitle' => 'Massage Amma assis : rapide, efficace, sans huile, sur chaise ergonomique.',
                    'teamTitle' => 'Pour vos equipes',
                    'teamBenefits' => [
                        'Moins de stress',
                        "Plus d'energie et de concentration",
                        'Moins de tensions musculaires',
                        'Plus de motivation',
                    ],
                    'companyTitle' => 'Pour votre entreprise',
                    'companyBenefits' => [
                        'Qualite de Vie au Travail renforcee',
                        'Collaborateurs plus performants et engages',
                        'Image positive et responsable',
                    ],
                    'characteristics' => ['10-20 min', 'Dans vos locaux', 'Sans huile', 'Chaise ergo'],
                    'quote' => 'Le massage Amma assis : un investissement simple et rentable pour le bien-etre collectif.',
                ]],
            ]
        );

        $this->createSystemPage(
            $manager,
            'about',
            'À propos',
            3,
            $now,
            [
                ['hero', 'Hero', ['title' => 'À propos', 'image' => null]],
                ['parcours', 'Parcours', ['title' => 'Mon parcours', 'image' => null, 'paragraphs' => []]],
                ['formations', 'Formations', ['title' => 'Formations', 'paragraphs' => [], 'image' => null]],
                ['philosophie', 'Philosophie', ['text' => '', 'author' => '']],
            ],
            'À propos'
        );

        $this->createSystemPage(
            $manager,
            'contact',
            'Contact',
            4,
            $now,
            [
                ['hero', 'Hero', ['title' => 'Contact', 'image' => null]],
                ['infos', 'Informations', [
                    'address' => [
                        'street' => '123 Rue du Bien-Etre',
                        'city' => '75011 Paris',
                    ],
                    'phone' => '06 12 34 56 78',
                    'email' => 'contact@helene-massage.fr',
                    'hours' => [
                        ['days' => 'Lundi - Vendredi', 'hours' => '10h - 20h'],
                        ['days' => 'Samedi', 'hours' => '10h - 18h'],
                    ],
                ]],
            ]
        );

        $this->createSystemPage(
            $manager,
            'mentions-legales',
            'Mentions légales',
            99,
            $now,
            [
                ['content', 'Contenu', ['title' => 'Mentions légales', 'paragraphs' => [], 'image' => null]],
            ],
            null,
            false
        );

        $manager->flush();
    }

    /**
     * @param array<int, array{0: string, 1: string, 2: array<string, mixed>}> $sectionsData
     */
    private function createSystemPage(
        ObjectManager $manager,
        string $slug,
        string $title,
        int $navOrder,
        \DateTimeImmutable $now,
        array $sectionsData,
        ?string $navTitle = null,
        bool $showInNav = true
    ): void {
        $existingPage = $manager->getRepository(Page::class)->findOneBy(['slug' => $slug]);
        if ($existingPage instanceof Page) {
            $page = $existingPage;
            $page
                ->setTitle($title)
                ->setMetaTitle($title)
                ->setShowInNav($showInNav)
                ->setNavOrder($navOrder)
                ->setNavTitle($navTitle)
                ->setUpdatedAt($now);
        } else {
            $page = (new Page())
                ->setSlug($slug)
                ->setTitle($title)
                ->setMetaTitle($title)
                ->setShowInNav($showInNav)
                ->setNavOrder($navOrder)
                ->setNavTitle($navTitle)
                ->setCreatedAt($now)
                ->setUpdatedAt($now);
            $manager->persist($page);
        }

        foreach ($sectionsData as $index => $data) {
            $sectionKey = $data[0];
            $sectionType = self::SECTION_TYPE_MAP[$sectionKey] ?? 'text';
            $existingSection = null;
            foreach ($page->getSections() as $section) {
                if ($section->getSectionKey() === $sectionKey) {
                    $existingSection = $section;
                    break;
                }
            }

            if ($existingSection instanceof PageSection) {
                $existingSection
                    ->setType($sectionType)
                    ->setTitle($data[1])
                    ->setContent($data[2])
                    ->setSortOrder($index)
                    ->setUpdatedAt($now);
                continue;
            }

            $newSection = (new PageSection())
                ->setSectionKey($sectionKey)
                ->setType($sectionType)
                ->setTitle($data[1])
                ->setContent($data[2])
                ->setSortOrder($index)
                ->setUpdatedAt($now);

            $page->addSection($newSection);
        }
    }
}
