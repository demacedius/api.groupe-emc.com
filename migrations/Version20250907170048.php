<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907170048 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Ajoute le champ updated_at à la table sell pour le suivi des modifications';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('sell');

        if (!$table->hasColumn('updated_at')) {
            $this->addSql('ALTER TABLE sell ADD updated_at DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('sell');

        if ($table->hasColumn('updated_at')) {
            $this->addSql('ALTER TABLE sell DROP updated_at');
        }
    }
}
