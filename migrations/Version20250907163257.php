<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907163257 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add missing columns to appointment table: replacement_date, appointment_date, teleoperator_name';
    }

    public function up(Schema $schema) : void
    {
        // Add missing columns to appointment table
        $this->addSql('ALTER TABLE appointment ADD replacement_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE appointment ADD appointment_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE appointment ADD teleoperator_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // Remove the added columns
        $this->addSql('ALTER TABLE appointment DROP replacement_date');
        $this->addSql('ALTER TABLE appointment DROP appointment_date');
        $this->addSql('ALTER TABLE appointment DROP teleoperator_name');
    }
}
