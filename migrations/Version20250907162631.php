<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907162631 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add missing updated_at column to user table';
    }

    public function up(Schema $schema) : void
    {
        // Add the missing updated_at column to user table
        $this->addSql('ALTER TABLE user ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // Remove the updated_at column
        $this->addSql('ALTER TABLE user DROP updated_at');
    }
}
