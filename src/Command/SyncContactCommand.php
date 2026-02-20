<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\PageRepository;
use App\Repository\SiteSettingsRepository;
use App\Service\ContactSettingsSync;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-contact',
    description: 'Synchronise les données contact entre Settings et la page Contact',
)]
final class SyncContactCommand extends Command
{
    public function __construct(
        private readonly ContactSettingsSync $contactSettingsSync,
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly PageRepository $pageRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('direction', InputArgument::REQUIRED, 'Direction: "to-page" ou "to-settings"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $direction = $input->getArgument('direction');

        if ($direction === 'to-page') {
            $settings = $this->siteSettingsRepository->findMain();
            if ($settings === null) {
                $io->error('Settings non trouvé');
                return Command::FAILURE;
            }

            $io->info('Données Settings avant sync:');
            $address = $settings->getAddress() ?? [];
            $io->listing([
                'Email: ' . $settings->getContactEmail(),
                'Phone: ' . ($settings->getContactPhone() ?? 'null'),
                'Address: ' . json_encode($address),
                'GoogleMapsEmbed: ' . ($settings->getGoogleMapsEmbed() ?? 'null'),
            ]);

            $this->contactSettingsSync->syncFromSettingsToPage($settings);
            $this->contactSettingsSync->syncMapFromSettings($settings);
            $this->entityManager->flush();

            $io->success('Sync Settings → Page Contact effectuée');

        } elseif ($direction === 'to-settings') {
            $contactPage = $this->pageRepository->findOneBy(['slug' => 'contact']);
            if ($contactPage === null) {
                $io->error('Page contact non trouvée');
                return Command::FAILURE;
            }

            $infosSection = null;
            $mapSection = null;
            foreach ($contactPage->getSections() as $section) {
                if ($section->getType() === 'contact-infos') {
                    $infosSection = $section;
                }
                if ($section->getType() === 'google-map') {
                    $mapSection = $section;
                }
            }

            if ($infosSection !== null) {
                $io->info('Données section contact-infos:');
                $io->listing([json_encode($infosSection->getContent(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]);
                $this->contactSettingsSync->syncFromPageToSettings($infosSection);
            } else {
                $io->warning('Section contact-infos non trouvée');
            }

            if ($mapSection !== null) {
                $io->info('Données section google-map:');
                $io->listing([json_encode($mapSection->getContent(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]);
                $this->contactSettingsSync->syncMapToSettings($mapSection);
            } else {
                $io->warning('Section google-map non trouvée');
            }

            $this->entityManager->flush();
            $io->success('Sync Page Contact → Settings effectuée');

        } else {
            $io->error('Direction invalide. Utilisez "to-page" ou "to-settings"');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
