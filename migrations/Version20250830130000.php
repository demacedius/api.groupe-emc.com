<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250830130000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Change sell_item.description to TEXT';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE sell_item CHANGE description description TEXT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM sell_item WHERE description IS NOT NULL AND LENGTH(description) > 255');

        if ($count === 0) {
            $this->addSql('ALTER TABLE sell_item CHANGE description description VARCHAR(255) DEFAULT NULL');
        }
    }
}
