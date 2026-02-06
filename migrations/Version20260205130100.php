<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205130100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_admin_user_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, alt VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(50) NOT NULL, size_bytes INT NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, uploaded_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_page_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_section (id INT AUTO_INCREMENT NOT NULL, section_key VARCHAR(50) NOT NULL, title VARCHAR(255) DEFAULT NULL, content JSON NOT NULL, sort_order INT NOT NULL, updated_at DATETIME NOT NULL, page_id INT NOT NULL, INDEX IDX_D713917AC4663E4 (page_id), UNIQUE INDEX uniq_page_section_page_key (page_id, section_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation_request (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, inscription VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, message LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, service_id INT DEFAULT NULL, INDEX IDX_5C02341AED5CA9E6 (service_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, category VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, prices JSON NOT NULL, highlight TINYINT NOT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE site_settings (id INT NOT NULL, site_name VARCHAR(255) NOT NULL, tagline VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, contact_email VARCHAR(255) NOT NULL, contact_phone VARCHAR(50) DEFAULT NULL, address JSON DEFAULT NULL, social_links JSON DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE page_section ADD CONSTRAINT FK_D713917AC4663E4 FOREIGN KEY (page_id) REFERENCES page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_request ADD CONSTRAINT FK_5C02341AED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE page_section DROP FOREIGN KEY FK_D713917AC4663E4');
        $this->addSql('ALTER TABLE reservation_request DROP FOREIGN KEY FK_5C02341AED5CA9E6');
        $this->addSql('DROP TABLE admin_user');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE page');
        $this->addSql('DROP TABLE page_section');
        $this->addSql('DROP TABLE reservation_request');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE site_settings');
    }
}
