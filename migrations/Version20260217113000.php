<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add explicit type column on page sections';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE massage_page_section ADD type VARCHAR(50) NOT NULL DEFAULT 'text'");
        $this->addSql("UPDATE massage_page_section SET type = 'hero' WHERE section_key = 'hero'");
        $this->addSql("UPDATE massage_page_section SET type = 'presentation' WHERE section_key = 'presentation'");
        $this->addSql("UPDATE massage_page_section SET type = 'approche' WHERE section_key = 'approche'");
        $this->addSql("UPDATE massage_page_section SET type = 'tarifs' WHERE section_key = 'tarifs'");
        $this->addSql("UPDATE massage_page_section SET type = 'entreprise' WHERE section_key = 'entreprise'");
        $this->addSql("UPDATE massage_page_section SET type = 'quote' WHERE section_key = 'philosophie'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE massage_page_section DROP type');
    }
}
