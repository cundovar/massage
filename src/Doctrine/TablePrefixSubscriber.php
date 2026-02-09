<?php

namespace App\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;

#[AsDoctrineListener(event: Events::loadClassMetadata)]
class TablePrefixSubscriber
{
    private string $prefix;

    public function __construct(string $prefix = 'massage_')
    {
        $this->prefix = $prefix;
    }

    public function __invoke(LoadClassMetadataEventArgs $args): void
    {
        $this->loadClassMetadata($args);
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $classMetadata = $args->getClassMetadata();

        if ($classMetadata->isEmbeddedClass || $classMetadata->isMappedSuperclass) {
            return;
        }

        $this->applyPrefix($classMetadata);
    }

    private function applyPrefix(ClassMetadata $classMetadata): void
    {
        $prefix = $this->prefix;
        if ($prefix === '') {
            return;
        }

        $tableName = $classMetadata->getTableName();
        if (!str_starts_with($tableName, $prefix)) {
            $classMetadata->setPrimaryTable(['name' => $prefix . $tableName]);
        }

        foreach ($classMetadata->associationMappings as $fieldName => $mapping) {
            if (!isset($mapping['joinTable']['name'])) {
                continue;
            }

            $joinTableName = $mapping['joinTable']['name'];
            if (!str_starts_with($joinTableName, $prefix)) {
                $mapping['joinTable']['name'] = $prefix . $joinTableName;
                $classMetadata->associationMappings[$fieldName] = $mapping;
            }
        }
    }
}
