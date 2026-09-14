<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PageSlugRedirectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageSlugRedirectRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_page_slug_redirect_old_slug', columns: ['old_slug'])]
class PageSlugRedirect
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Page $page = null;

    #[ORM\Column(name: 'old_slug', length: 100)]
    private string $oldSlug;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(Page $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getOldSlug(): string
    {
        return $this->oldSlug;
    }

    public function setOldSlug(string $oldSlug): self
    {
        $this->oldSlug = $oldSlug;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
