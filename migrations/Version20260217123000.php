<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure entreprise page section contains default business content in DB';
    }

    public function up(Schema $schema): void
    {
        $content = <<<'JSON'
{"title":"Massage Amma en entreprise","subtitle":"Massage Amma assis : rapide, efficace, sans huile, sur chaise ergonomique.","teamTitle":"Pour vos equipes","teamBenefits":["Moins de stress","Plus d'energie et de concentration","Moins de tensions musculaires","Plus de motivation"],"companyTitle":"Pour votre entreprise","companyBenefits":["Qualite de Vie au Travail renforcee","Collaborateurs plus performants et engages","Image positive et responsable"],"characteristics":["10-20 min","Dans vos locaux","Sans huile","Chaise ergo"],"quote":"Le massage Amma assis : un investissement simple et rentable pour le bien-etre collectif."}
JSON;

        $this->addSql(<<<'SQL'
INSERT INTO massage_page (slug, title, meta_title, meta_description, show_in_nav, nav_order, nav_title, created_at, updated_at)
SELECT 'entreprise', 'Entreprise', 'Entreprise', NULL, 1, 2, NULL, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM massage_page WHERE slug = 'entreprise'
)
SQL
        );

        $this->addSql(
            "UPDATE massage_page SET title = 'Entreprise', meta_title = 'Entreprise', show_in_nav = 1, nav_order = 2, nav_title = NULL, updated_at = NOW() WHERE slug = 'entreprise'"
        );

        $this->addSql(
            "INSERT INTO massage_page_section (section_key, type, title, content, sort_order, updated_at, page_id)
             SELECT 'entreprise', 'entreprise', 'Entreprise', ?, 0, NOW(), p.id
             FROM massage_page p
             WHERE p.slug = 'entreprise'
               AND NOT EXISTS (
                   SELECT 1 FROM massage_page_section s WHERE s.page_id = p.id AND s.section_key = 'entreprise'
               )",
            [$content]
        );

        $this->addSql(
            "UPDATE massage_page_section s
             INNER JOIN massage_page p ON p.id = s.page_id
             SET s.type = 'entreprise',
                 s.title = 'Entreprise',
                 s.content = ?,
                 s.sort_order = 0,
                 s.updated_at = NOW()
             WHERE p.slug = 'entreprise' AND s.section_key = 'entreprise'",
            [$content]
        );
    }

    public function down(Schema $schema): void
    {
        $this->write('No automatic rollback for data migration Version20260217123000.');
    }
}
