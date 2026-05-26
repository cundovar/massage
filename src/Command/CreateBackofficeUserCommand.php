<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:backoffice:user:create', description: 'Create a backoffice admin or dev user')]
final class CreateBackofficeUserCommand extends Command
{
    public function __construct(
        private readonly AdminUserRepository $adminUserRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('name', InputArgument::REQUIRED, 'Display name')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'User role: admin or dev', 'dev')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Plain password. If omitted, the command asks for it.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = strtolower(trim((string) $input->getArgument('email')));
        $name = trim((string) $input->getArgument('name'));
        $role = strtolower(trim((string) $input->getOption('role')));
        $password = (string) ($input->getOption('password') ?? '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $io->error('Invalid email.');

            return Command::INVALID;
        }

        if ($name === '') {
            $io->error('Name is required.');

            return Command::INVALID;
        }

        $roleMap = [
            'admin' => AdminUser::ROLE_ADMIN,
            'dev' => AdminUser::ROLE_DEV,
        ];

        if (!isset($roleMap[$role])) {
            $io->error('Role must be "admin" or "dev".');

            return Command::INVALID;
        }

        if ($this->adminUserRepository->findOneBy(['email' => $email]) !== null) {
            $io->error('A user with this email already exists.');

            return Command::FAILURE;
        }

        if ($password === '') {
            $password = (string) $io->askHidden('Password');
        }

        if (strlen($password) < 8) {
            $io->error('Password must contain at least 8 characters.');

            return Command::INVALID;
        }

        $user = (new AdminUser())
            ->setEmail($email)
            ->setName($name)
            ->setRoles([$roleMap[$role]]);

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Backoffice %s user created: %s', $role, $email));

        return Command::SUCCESS;
    }
}
