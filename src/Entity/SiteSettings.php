<?php

namespace App\Entity;

use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteSettingsRepository::class)]
class SiteSettings
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(length: 255)]
    private string $siteName;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tagline = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 255)]
    private string $contactEmail;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contactPhone = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $address = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $socialLinks = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getSiteName(): string
    {
        return $this->siteName;
    }

    public function setSiteName(string $siteName): self
    {
        $this->siteName = $siteName;

        return $this;
    }

    public function getTagline(): ?string
    {
        return $this->tagline;
    }

    public function setTagline(?string $tagline): self
    {
        $this->tagline = $tagline;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): self
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getAddress(): ?array
    {
        return $this->address;
    }

    /** @param array<string, mixed>|null $address */
    public function setAddress(?array $address): self
    {
        $this->address = $address;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getSocialLinks(): ?array
    {
        return $this->socialLinks;
    }

    /** @param array<string, mixed>|null $socialLinks */
    public function setSocialLinks(?array $socialLinks): self
    {
        $this->socialLinks = $socialLinks;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
