<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221123212301 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE work_sheet (id INT AUTO_INCREMENT NOT NULL, sell_id INT NOT NULL, service_category_id INT NOT NULL, task VARCHAR(255) NOT NULL, surface INT NOT NULL, content JSON NOT NULL, accessibility VARCHAR(255) DEFAULT NULL, comment VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_AFC7EACDACE745F4 (sell_id), INDEX IDX_AFC7EACDDEDCBB4E (service_category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE work_sheet ADD CONSTRAINT FK_AFC7EACDACE745F4 FOREIGN KEY (sell_id) REFERENCES sell (id)');
        $this->addSql('ALTER TABLE work_sheet ADD CONSTRAINT FK_AFC7EACDDEDCBB4E FOREIGN KEY (service_category_id) REFERENCES service_category (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE work_sheet');
    }
}
