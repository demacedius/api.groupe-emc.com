<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Increase length of migration_versions.version to support descriptive class names.
 */
final class Version20250928UpdateMigrationVersionsLength extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enlarge migration_versions.version column to accommodate long migration class names.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \"mysql\".');

        $this->addSql('ALTER TABLE migration_versions MODIFY version VARCHAR(191) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \"mysql\".');

        $this->addSql('ALTER TABLE migration_versions MODIFY version VARCHAR(14) NOT NULL');
    }
}
