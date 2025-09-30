<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907163530 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add missing client_code column to customer table';
    }

    public function up(Schema $schema) : void
    {
        // Add missing client_code column to customer table
        $this->addSql('ALTER TABLE customer ADD client_code VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // Remove the client_code column
        $this->addSql('ALTER TABLE customer DROP client_code');
    }
}
