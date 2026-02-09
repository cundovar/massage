<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AdminUser;
use App\Entity\Page;
use App\Entity\PageSection;
use App\Entity\Service;
use App\Entity\SiteSettings;
use App\Repository\AdminUserRepository;
use App\Repository\PageRepository;
use App\Repository\ServiceRepository;
use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed:initial-data', description: 'Seed initial website content and admin user')]
final class SeedInitialDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly PageRepository $pageRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly AdminUserRepository $adminUserRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->seedSettings();
        $this->seedPages();
        $this->seedServices();
        $this->seedAdminUser();

        $this->entityManager->flush();

        $output->writeln('Initial data seeded.');

        return Command::SUCCESS;
    }

    private function seedSettings(): void
    {
        $settings = $this->siteSettingsRepository->find(1);
        if ($settings !== null) {
            return;
        }

        $settings = (new SiteSettings())
            ->setId(1)
            ->setSiteName('Helene - Massages & Ayurveda')
            ->setTagline('Une pause bienveillante')
            ->setLogo('/images/logo.png')
            ->setContactEmail('contact@helene-massage.fr')
            ->setContactPhone('06 12 34 56 78')
            ->setAddress([
                'street' => '123 Rue du Bien-Etre',
                'city' => '75011 Paris',
            ])
            ->setSocialLinks([
                'instagram' => 'https://instagram.com/helene_massage',
            ])
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($settings);
    }

    private function seedPages(): void
    {
        $this->createPageIfMissing('home', 'Accueil', 'Massage Ayurveda a Paris - Helene', 'Massages ayurvediques, reflexologie, kobido et prenatal.', [
            'hero' => [
                'title' => null,
                'content' => [
                    'siteTitle' => 'Helene - Massages & Ayurveda',
                    'slides' => [
                        [
                            'title' => 'Une pause pour vous recentrer',
                            'subtitle' => 'Massages ayurvediques - Kobido',
                            'image' => '/images/default/hero-1.jpg',
                        ],
                    ],
                ],
            ],
            'presentation' => [
                'title' => 'Presentation',
                'content' => [
                    'title' => 'Presentation',
                    'paragraphs' => [
                        'Bienvenue, je suis Helene.',
                        'Chaque seance est personnalisee selon vos besoins.',
                    ],
                    'quote' => "Je m'adresse a tous ceux qui souhaitent prendre soin d'eux-memes et s'offrir une pause bienveillante.",
                ],
            ],
            'approche' => [
                'title' => 'Approche',
                'content' => [
                    'images' => ['/images/default/approche.jpg'],
                    'bulletsTitle' => 'Ce qui guide mes mains :',
                    'bullets' => [
                        'Un entretien prealable.',
                        'Une ecoute precise du corps.',
                        'Une parenthese bienveillante.',
                    ],
                    'quote' => 'Chaque soin est pense comme une pause pour vous recentrer et vous alleger.',
                ],
            ],
        ]);
        $this->ensureHomeSections();

        $this->createPageIfMissing('soins', 'Soins', 'Soins & Massages - Helene', 'Decouvrez les soins ayurveda, reflexologie, kobido et prenatal.', [
            'hero' => [
                'title' => 'Soins & Massages',
                'content' => [
                    'image' => '/images/default/soins-hero.jpg',
                ],
            ],
            'intro' => [
                'title' => 'Introduction',
                'content' => [
                    'paragraphs' => ['Chaque soin est pense comme un moment unique.'],
                ],
            ],
            'tarifs' => [
                'title' => 'Carte & tarifs',
                'content' => [
                    'title' => 'Carte & tarifs',
                    'subtitle' => 'Une selection de soins ayurvediques, reflexologie plantaire, Kobido et massage prenatal.',
                    'offers' => [
                        [
                            'title' => 'Ayurveda',
                            'description' => "Les massages ayurvediques apaisent le corps et l'esprit, redonnent de l'ancrage et relancent la flamme interieure.",
                            'prices' => ['Abhyanga · 1h · 80 EUR', 'Abhyanga · 1h30 · 100 EUR'],
                        ],
                        [
                            'title' => 'Bol Kansu',
                            'description' => "Le bol aux trois alliages est frotte contre la plante des pieds pour apaiser les esprits agites et reequilibrer l'element feu.",
                            'prices' => ['Massage des pieds · 1h · 60 EUR'],
                        ],
                        [
                            'title' => 'Padhabyanga',
                            'description' => "Massage des jambes complete d'une reflexologie plantaire ou d'un bol Kansu pour delier les tensions.",
                            'prices' => ['1h · 70 EUR'],
                        ],
                        [
                            'title' => 'Massage prenatal',
                            'description' => 'Un accompagnement en douceur pendant la grossesse. Le soin est adapte a chaque etape selon vos besoins.',
                            'prices' => ["Tarif communique lors de l'entretien prealable."],
                        ],
                        [
                            'title' => 'Reflexologie plantaire',
                            'description' => 'En regulant les differents systemes du corps par le pied, cette pratique aide a reequilibrer le moment.',
                            'prices' => ['Seance decouverte · 45 min · 50 EUR'],
                        ],
                        [
                            'title' => 'Kobido',
                            'description' => 'Massage du visage de tradition japonaise. Il fait circuler la lymphe et redonne du tonus pour un visage lumineux.',
                            'prices' => ['Seance decouverte · 70 EUR'],
                        ],
                    ],
                ],
            ],
        ]);
        $this->ensureSoinsSections();

        $this->createPageIfMissing('about', 'A propos', 'A propos - Helene', 'Parcours et philosophie d Helene.', [
            'hero' => [
                'title' => 'A propos',
                'content' => ['image' => '/images/default/about-hero.jpg'],
            ],
        ]);

        $this->createPageIfMissing('contact', 'Contact', 'Contact - Helene', 'Contactez Helene pour reserver.', [
            'infos' => [
                'title' => 'Informations pratiques',
                'content' => [
                    'address' => ['street' => '123 Rue du Bien-Etre', 'city' => '75011 Paris'],
                    'phone' => '06 12 34 56 78',
                    'email' => 'contact@helene-massage.fr',
                ],
            ],
        ]);

        $this->createPageIfMissing('mentions-legales', 'Mentions legales', 'Mentions legales - Helene', 'Mentions legales et RGPD.', [
            'content' => [
                'title' => 'Mentions legales',
                'content' => [
                    'sections' => [
                        ['title' => 'Editeur', 'content' => 'Helene [Nom]'],
                    ],
                ],
            ],
        ]);

        $this->createPageIfMissing('entreprise', 'Entreprise', 'Massage Amma en entreprise - Helene', 'Offre de massage Amma assis pour les entreprises.', [
            'entreprise' => [
                'title' => 'Massage Amma en entreprise',
                'content' => [
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
                ],
            ],
        ]);
        $this->ensureEntrepriseSections();
    }

    private function ensureHomeSections(): void
    {
        $page = $this->pageRepository->findOneBy(['slug' => 'home']);
        if ($page === null) {
            return;
        }

        $this->ensureSection($page, 'hero', null, [
            'siteTitle' => 'Helene - Massages & Ayurveda',
            'slides' => [
                [
                    'title' => 'Une pause pour vous recentrer',
                    'subtitle' => 'Massages ayurvediques - Kobido',
                    'image' => '/images/default/hero-1.jpg',
                ],
            ],
        ]);
        $this->ensureSection($page, 'presentation', 'Presentation', [
            'title' => 'Presentation',
            'paragraphs' => [
                'Bienvenue, je suis Helene.',
                'Chaque seance est personnalisee selon vos besoins.',
            ],
            'quote' => "Je m'adresse a tous ceux qui souhaitent prendre soin d'eux-memes et s'offrir une pause bienveillante.",
        ]);
        $this->ensureSection($page, 'approche', 'Approche', [
            'images' => ['/images/default/approche.jpg'],
            'bulletsTitle' => 'Ce qui guide mes mains :',
            'bullets' => [
                'Un entretien prealable.',
                'Une ecoute precise du corps.',
                'Une parenthese bienveillante.',
            ],
        ]);
    }

    private function ensureSoinsSections(): void
    {
        $page = $this->pageRepository->findOneBy(['slug' => 'soins']);
        if ($page === null) {
            return;
        }

        $this->ensureSection($page, 'hero', 'Soins & Massages', [
            'image' => '/images/default/soins-hero.jpg',
        ]);
        $this->ensureSection($page, 'intro', 'Introduction', [
            'paragraphs' => ['Chaque soin est pense comme un moment unique.'],
        ]);
        $this->ensureSection($page, 'tarifs', 'Carte & tarifs', [
            'title' => 'Carte & tarifs',
            'subtitle' => 'Une selection de soins ayurvediques, reflexologie plantaire, Kobido et massage prenatal.',
            'offers' => [
                [
                    'title' => 'Ayurveda',
                    'description' => "Les massages ayurvediques apaisent le corps et l'esprit, redonnent de l'ancrage et relancent la flamme interieure.",
                    'prices' => ['Abhyanga · 1h · 80 EUR', 'Abhyanga · 1h30 · 100 EUR'],
                ],
                [
                    'title' => 'Bol Kansu',
                    'description' => "Le bol aux trois alliages est frotte contre la plante des pieds pour apaiser les esprits agites et reequilibrer l'element feu.",
                    'prices' => ['Massage des pieds · 1h · 60 EUR'],
                ],
                [
                    'title' => 'Padhabyanga',
                    'description' => "Massage des jambes complete d'une reflexologie plantaire ou d'un bol Kansu pour delier les tensions.",
                    'prices' => ['1h · 70 EUR'],
                ],
                [
                    'title' => 'Massage prenatal',
                    'description' => 'Un accompagnement en douceur pendant la grossesse. Le soin est adapte a chaque etape selon vos besoins.',
                    'prices' => ["Tarif communique lors de l'entretien prealable."],
                ],
                [
                    'title' => 'Reflexologie plantaire',
                    'description' => 'En regulant les differents systemes du corps par le pied, cette pratique aide a reequilibrer le moment.',
                    'prices' => ['Seance decouverte · 45 min · 50 EUR'],
                ],
                [
                    'title' => 'Kobido',
                    'description' => 'Massage du visage de tradition japonaise. Il fait circuler la lymphe et redonne du tonus pour un visage lumineux.',
                    'prices' => ['Seance decouverte · 70 EUR'],
                ],
            ],
        ]);
    }

    private function ensureEntrepriseSections(): void
    {
        $page = $this->pageRepository->findOneBy(['slug' => 'entreprise']);
        if ($page === null) {
            return;
        }

        $this->ensureSection($page, 'entreprise', 'Massage Amma en entreprise', [
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
        ]);
    }

    /** @param array<string, array{title: ?string, content: array<string, mixed>}> $sections */
    private function createPageIfMissing(string $slug, string $title, string $metaTitle, string $metaDescription, array $sections): void
    {
        if ($this->pageRepository->findOneBy(['slug' => $slug]) !== null) {
            return;
        }

        $page = (new Page())
            ->setSlug($slug)
            ->setTitle($title)
            ->setMetaTitle($metaTitle)
            ->setMetaDescription($metaDescription)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $order = 0;
        foreach ($sections as $key => $sectionData) {
            $section = (new PageSection())
                ->setSectionKey($key)
                ->setTitle($sectionData['title'])
                ->setContent($sectionData['content'])
                ->setSortOrder($order++)
                ->setUpdatedAt(new \DateTimeImmutable());

            $page->addSection($section);
        }

        $this->entityManager->persist($page);
    }

    /** @param array<string, mixed> $content */
    private function ensureSection(Page $page, string $key, ?string $title, array $content): void
    {
        foreach ($page->getSections() as $section) {
            if ($section->getSectionKey() === $key) {
                return;
            }
        }

        $order = $page->getSections()->count();
        $section = (new PageSection())
            ->setSectionKey($key)
            ->setTitle($title)
            ->setContent($content)
            ->setSortOrder($order)
            ->setUpdatedAt(new \DateTimeImmutable());

        $page->addSection($section);
        $page->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($page);
    }

    private function seedServices(): void
    {
        if ($this->serviceRepository->count([]) > 0) {
            return;
        }

        $services = [
            [
                'category' => 'Ayurveda',
                'name' => "Massage ayurvedique a l'huile chaude",
                'description' => 'Apaisant et ancrant.',
                'prices' => [['label' => '1h', 'price' => 80], ['label' => '1h30', 'price' => 100]],
                'highlight' => true,
            ],
            [
                'category' => 'Kobido',
                'name' => 'Massage du visage Kobido',
                'description' => 'Tradition japonaise.',
                'prices' => [['label' => 'Seance', 'price' => 70]],
                'highlight' => false,
            ],
        ];

        foreach ($services as $index => $data) {
            $service = (new Service())
                ->setCategory($data['category'])
                ->setName($data['name'])
                ->setDescription($data['description'])
                ->setPrices($data['prices'])
                ->setHighlight($data['highlight'])
                ->setSortOrder($index)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($service);
        }
    }

    private function seedAdminUser(): void
    {
        $email = 'admin@helene-massage.fr';

        if ($this->adminUserRepository->findOneBy(['email' => $email]) !== null) {
            return;
        }

        $plainPassword = $_ENV['ADMIN_SEED_PASSWORD'] ?? 'ChangeMe123!';

        $admin = (new AdminUser())
            ->setEmail($email)
            ->setName('Admin Helene');

        $admin->setPassword($this->passwordHasher->hashPassword($admin, $plainPassword));

        $this->entityManager->persist($admin);
    }
}
