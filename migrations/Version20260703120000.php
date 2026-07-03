<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset fields to admin users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE massage_admin_user ADD password_reset_token_hash VARCHAR(64) DEFAULT NULL, ADD password_reset_requested_at DATETIME DEFAULT NULL, ADD password_reset_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE massage_admin_user DROP password_reset_token_hash, DROP password_reset_requested_at, DROP password_reset_expires_at');
    }
}
