<?php

namespace App\Entity;

use App\Repository\PageSectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageSectionRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_page_section_page_key', columns: ['page_id', 'section_key'])]
class PageSection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Page $page = null;

    #[ORM\Column(name: 'section_key', length: 50)]
    private string $sectionKey;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $content = [];

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getSectionKey(): string
    {
        return $this->sectionKey;
    }

    public function setSectionKey(string $sectionKey): self
    {
        $this->sectionKey = $sectionKey;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getContent(): array
    {
        return $this->content;
    }

    /** @param array<string, mixed> $content */
    public function setContent(array $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

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
