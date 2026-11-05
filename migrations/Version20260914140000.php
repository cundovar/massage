<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store previous page slugs for permanent public URL redirects';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE massage_page_slug_redirect (id INT AUTO_INCREMENT NOT NULL, page_id INT NOT NULL, old_slug VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_5135E416C4663E4 (page_id), UNIQUE INDEX uniq_page_slug_redirect_old_slug (old_slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE massage_page_slug_redirect ADD CONSTRAINT FK_5135E416C4663E4 FOREIGN KEY (page_id) REFERENCES massage_page (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE massage_page_slug_redirect');
    }
}
