<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add navigation_data column to site_settings for external links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE massage_site_settings ADD navigation_data JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE massage_site_settings DROP navigation_data');
    }
}
