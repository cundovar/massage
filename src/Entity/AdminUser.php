<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AdminUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AdminUserRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_admin_user_email', columns: ['email'])]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_DEV = 'ROLE_DEV';
    public const ROLE_USER = 'ROLE_USER';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $password;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $passwordResetTokenHash = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordResetRequestedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordResetExpiresAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = [self::ROLE_ADMIN];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = strtolower(trim($email));

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPasswordResetTokenHash(): ?string
    {
        return $this->passwordResetTokenHash;
    }

    public function setPasswordResetTokenHash(?string $passwordResetTokenHash): self
    {
        $this->passwordResetTokenHash = $passwordResetTokenHash;

        return $this;
    }

    public function getPasswordResetRequestedAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetRequestedAt;
    }

    public function setPasswordResetRequestedAt(?\DateTimeImmutable $passwordResetRequestedAt): self
    {
        $this->passwordResetRequestedAt = $passwordResetRequestedAt;

        return $this;
    }

    public function getPasswordResetExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetExpiresAt;
    }

    public function setPasswordResetExpiresAt(?\DateTimeImmutable $passwordResetExpiresAt): self
    {
        $this->passwordResetExpiresAt = $passwordResetExpiresAt;

        return $this;
    }

    public function clearPasswordResetToken(): self
    {
        $this->passwordResetTokenHash = null;
        $this->passwordResetRequestedAt = null;
        $this->passwordResetExpiresAt = null;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array(self::ROLE_USER, $roles, true)) {
            $roles[] = self::ROLE_USER;
        }

        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): self
    {
        $allowedRoles = [self::ROLE_ADMIN, self::ROLE_DEV, self::ROLE_USER];
        $this->roles = array_values(array_unique(array_filter(
            $roles,
            static fn (string $role): bool => in_array($role, $allowedRoles, true),
        )));

        return $this;
    }

    public function eraseCredentials(): void
    {
    }
}
