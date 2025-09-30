<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour mettre à jour les informations SIRET et TVA de FPEMC
 */
final class Version20250908211500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Met à jour SIRET et TVA pour France Patrimoine EMC';
    }

    public function up(Schema $schema): void
    {
        // Mettre à jour France Patrimoine EMC avec SIRET et TVA
        $this->addSql("UPDATE company SET siret = '801 442 658 00028', tva_intra = 'FR44 801 442 658' WHERE name = 'France Patrimoine EMC' OR prefix = 'FP'");
    }

    public function down(Schema $schema): void
    {
        // Annuler les changements
        $this->addSql("UPDATE company SET siret = NULL, tva_intra = NULL WHERE name = 'France Patrimoine EMC' OR prefix = 'FP'");
    }
}