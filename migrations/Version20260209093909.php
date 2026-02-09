<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209093909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE massage_admin_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_admin_user_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE massage_media (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, alt VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(50) NOT NULL, size_bytes INT NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, uploaded_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE massage_page (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_page_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE massage_page_section (id INT AUTO_INCREMENT NOT NULL, section_key VARCHAR(50) NOT NULL, title VARCHAR(255) DEFAULT NULL, content JSON NOT NULL, sort_order INT NOT NULL, updated_at DATETIME NOT NULL, page_id INT NOT NULL, INDEX IDX_FA7AA97EC4663E4 (page_id), UNIQUE INDEX uniq_page_section_page_key (page_id, section_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE massage_reservation_request (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, inscription VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, message LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, service_id INT DEFAULT NULL, INDEX IDX_861EF716ED5CA9E6 (service_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE massage_service (id INT AUTO_INCREMENT NOT NULL, category VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, prices JSON NOT NULL, highlight TINYINT NOT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE massage_site_settings (id INT NOT NULL, site_name VARCHAR(255) NOT NULL, tagline VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, contact_email VARCHAR(255) NOT NULL, contact_phone VARCHAR(50) DEFAULT NULL, address JSON DEFAULT NULL, social_links JSON DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE massage_page_section ADD CONSTRAINT FK_FA7AA97EC4663E4 FOREIGN KEY (page_id) REFERENCES massage_page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE massage_reservation_request ADD CONSTRAINT FK_861EF716ED5CA9E6 FOREIGN KEY (service_id) REFERENCES massage_service (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE massage_page_section DROP FOREIGN KEY FK_FA7AA97EC4663E4');
        $this->addSql('ALTER TABLE massage_reservation_request DROP FOREIGN KEY FK_861EF716ED5CA9E6');
        $this->addSql('DROP TABLE massage_admin_user');
        $this->addSql('DROP TABLE massage_media');
        $this->addSql('DROP TABLE massage_page');
        $this->addSql('DROP TABLE massage_page_section');
        $this->addSql('DROP TABLE massage_reservation_request');
        $this->addSql('DROP TABLE massage_service');
        $this->addSql('DROP TABLE massage_site_settings');
    }
}
