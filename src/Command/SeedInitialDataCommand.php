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
                    'paragraphs' => [
                        'Bienvenue, je suis Helene.',
                        'Chaque seance est personnalisee selon vos besoins.',
                    ],
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
        ]);

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
            'paragraphs' => [
                'Bienvenue, je suis Helene.',
                'Chaque seance est personnalisee selon vos besoins.',
            ],
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
