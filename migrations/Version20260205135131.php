<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205135131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_page_section_page_key ON page_section');
        $this->addSql('ALTER TABLE page_section CHANGE `key` section_key VARCHAR(50) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_page_section_page_key ON page_section (page_id, section_key)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_page_section_page_key ON page_section');
        $this->addSql('ALTER TABLE page_section CHANGE section_key `key` VARCHAR(50) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_page_section_page_key ON page_section (page_id, `key`)');
    }
}
