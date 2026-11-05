<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PageSlugRedirect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PageSlugRedirect>
 */
class PageSlugRedirectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageSlugRedirect::class);
    }

    public function findOneByOldSlug(string $oldSlug): ?PageSlugRedirect
    {
        return $this->findOneBy(['oldSlug' => $oldSlug]);
    }
}
