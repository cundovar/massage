<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix contact infos section content shape in DB';
    }

    public function up(Schema $schema): void
    {
        $content = <<<'JSON'
{"address":{"street":"123 Rue du Bien-Etre","city":"75011 Paris"},"phone":"06 12 34 56 78","email":"contact@helene-massage.fr","hours":[{"days":"Lundi - Vendredi","hours":"10h - 20h"},{"days":"Samedi","hours":"10h - 18h"}]}
JSON;

        $this->addSql(
            "UPDATE massage_page_section s
             INNER JOIN massage_page p ON p.id = s.page_id
             SET s.type = 'text',
                 s.title = 'Informations',
                 s.content = ?,
                 s.updated_at = NOW()
             WHERE p.slug = 'contact' AND s.section_key = 'infos'",
            [$content]
        );
    }

    public function down(Schema $schema): void
    {
        $this->write('No automatic rollback for data migration Version20260217124500.');
    }
}
