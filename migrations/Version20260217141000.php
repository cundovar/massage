<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217141000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extend site settings with nested settings support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE massage_site_settings ADD favicon VARCHAR(255) DEFAULT NULL, ADD default_meta_description LONGTEXT DEFAULT NULL, ADD google_maps_url LONGTEXT DEFAULT NULL, ADD google_maps_embed LONGTEXT DEFAULT NULL, ADD hours_data JSON DEFAULT NULL, ADD booking_data JSON DEFAULT NULL, ADD appearance_data JSON DEFAULT NULL, ADD footer_data JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE massage_site_settings DROP favicon, DROP default_meta_description, DROP google_maps_url, DROP google_maps_embed, DROP hours_data, DROP booking_data, DROP appearance_data, DROP footer_data');
    }
}
