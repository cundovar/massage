<?php

namespace App\Entity;

use App\Repository\SiteBuilderProjectRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * The single GrapesJS project holding every page of the site.
 *
 * Projects are kept as raw JSON text: decoding them to PHP arrays would turn
 * empty objects into empty lists and corrupt the GrapesJS data.
 */
#[ORM\Entity(repositoryClass: SiteBuilderProjectRepository::class)]
class SiteBuilderProject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text', length: 4294967295)]
    private string $project;

    #[ORM\Column(type: 'text', length: 4294967295, nullable: true)]
    private ?string $previousProject = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $project)
    {
        $this->project = $project;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): string
    {
        return $this->project;
    }

    public function getPreviousProject(): ?string
    {
        return $this->previousProject;
    }

    /** Replaces the project and keeps the current one as the previous version. */
    public function replaceProject(string $project): self
    {
        $this->previousProject = $this->project;
        $this->project = $project;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
