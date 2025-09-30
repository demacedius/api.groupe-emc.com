<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220619140021 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE sell ADD source VARCHAR(255) DEFAULT NULL, ADD salesman VARCHAR(255) DEFAULT NULL, ADD work_date DATE DEFAULT NULL, ADD workers VARCHAR(255) DEFAULT NULL, ADD twoyear TINYINT(1) DEFAULT NULL, ADD tenyear TINYINT(1) DEFAULT NULL, ADD followup VARCHAR(255) DEFAULT NULL, ADD fees VARCHAR(255) DEFAULT NULL, ADD details LONGTEXT DEFAULT NULL');
        // $this->addSql('ALTER TABLE sell ADD CONSTRAINT FK_9B9ED07D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE sell DROP FOREIGN KEY FK_9B9ED07D979B1AD6');
        $this->addSql('ALTER TABLE sell DROP source, DROP salesman, DROP work_date, DROP workers, DROP twoyear, DROP tenyear, DROP followup, DROP fees, DROP details');
    }
}
