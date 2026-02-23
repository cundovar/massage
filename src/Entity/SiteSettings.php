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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $favicon = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $defaultMetaDescription = null;

    #[ORM\Column(length: 255)]
    private string $contactEmail;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $googleMapsUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $googleMapsEmbed = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $address = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $socialLinks = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $hoursData = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $bookingData = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $appearanceData = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $footerData = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $navigationData = null;

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

    public function getFavicon(): ?string
    {
        return $this->favicon;
    }

    public function setFavicon(?string $favicon): self
    {
        $this->favicon = $favicon;

        return $this;
    }

    public function getDefaultMetaDescription(): ?string
    {
        return $this->defaultMetaDescription;
    }

    public function setDefaultMetaDescription(?string $defaultMetaDescription): self
    {
        $this->defaultMetaDescription = $defaultMetaDescription;

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

    public function getGoogleMapsUrl(): ?string
    {
        return $this->googleMapsUrl;
    }

    public function setGoogleMapsUrl(?string $googleMapsUrl): self
    {
        $this->googleMapsUrl = $googleMapsUrl;

        return $this;
    }

    public function getGoogleMapsEmbed(): ?string
    {
        return $this->googleMapsEmbed;
    }

    public function setGoogleMapsEmbed(?string $googleMapsEmbed): self
    {
        $this->googleMapsEmbed = $googleMapsEmbed;

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

    /** @return array<string, mixed>|null */
    public function getHoursData(): ?array
    {
        return $this->hoursData;
    }

    /** @param array<string, mixed>|null $hoursData */
    public function setHoursData(?array $hoursData): self
    {
        $this->hoursData = $hoursData;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getBookingData(): ?array
    {
        return $this->bookingData;
    }

    /** @param array<string, mixed>|null $bookingData */
    public function setBookingData(?array $bookingData): self
    {
        $this->bookingData = $bookingData;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getAppearanceData(): ?array
    {
        return $this->appearanceData;
    }

    /** @param array<string, mixed>|null $appearanceData */
    public function setAppearanceData(?array $appearanceData): self
    {
        $this->appearanceData = $appearanceData;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getFooterData(): ?array
    {
        return $this->footerData;
    }

    /** @param array<string, mixed>|null $footerData */
    public function setFooterData(?array $footerData): self
    {
        $this->footerData = $footerData;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getNavigationData(): ?array
    {
        return $this->navigationData;
    }

    /** @param array<string, mixed>|null $navigationData */
    public function setNavigationData(?array $navigationData): self
    {
        $this->navigationData = $navigationData;

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
