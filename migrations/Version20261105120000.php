<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the GrapesJS project and the HTML and CSS exported for each page';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE massage_site_builder_project (id INT AUTO_INCREMENT NOT NULL, project LONGTEXT NOT NULL, previous_project LONGTEXT DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE massage_page ADD html LONGTEXT DEFAULT NULL, ADD css LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE massage_site_builder_project');
        $this->addSql('ALTER TABLE massage_page DROP html, DROP css');
    }
}
