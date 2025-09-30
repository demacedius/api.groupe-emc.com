<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ensure missing columns removed during manual rollbacks are recreated.
 */
final class Version20250928135302 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Re-add user.updated_at column if missing after rollback mishaps.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $table = $schema->getTable('user');

        if (!$table->hasColumn('updated_at')) {
            $this->addSql('ALTER TABLE user ADD updated_at DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $table = $schema->getTable('user');

        if ($table->hasColumn('updated_at')) {
            $this->addSql('ALTER TABLE user DROP updated_at');
        }
    }
}
