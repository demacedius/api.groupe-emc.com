<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907165806 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Ajoute les champs SIRET et TVA intra-communautaire à la table company';
    }

    public function up(Schema $schema) : void
    {
        // Add SIRET and TVA fields to company table
        $this->addSql('ALTER TABLE company ADD siret VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE company ADD tva_intra VARCHAR(255) DEFAULT NULL');
        
        // Update existing companies with the specific values mentioned
        $this->addSql("UPDATE company SET siret = '801 442 658 00028', tva_intra = 'FR44 801 442 658' WHERE name LIKE '%FPEMC%'");
        $this->addSql("UPDATE company SET siret = '877 566 562 00022', tva_intra = 'FR00877566562' WHERE name LIKE '%Mon patrimoine%'");
        $this->addSql("UPDATE company SET address = '13 rue du belvédère', postcode = '94430', city = 'Chennevières sur marne' WHERE name LIKE '%Mon patrimoine%'");
    }

    public function down(Schema $schema) : void
    {
        // Remove SIRET and TVA fields
        $this->addSql('ALTER TABLE company DROP siret');
        $this->addSql('ALTER TABLE company DROP tva_intra');
    }
}
