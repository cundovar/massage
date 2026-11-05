<?php

namespace App\Repository;

use App\Entity\SiteBuilderProject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteBuilderProject>
 */
class SiteBuilderProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteBuilderProject::class);
    }

    public function findMain(): ?SiteBuilderProject
    {
        return $this->findOneBy([]);
    }

    /** Must run inside a transaction. */
    public function findMainForUpdate(): ?SiteBuilderProject
    {
        return $this->createQueryBuilder('project')
            ->setMaxResults(1)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }
}
