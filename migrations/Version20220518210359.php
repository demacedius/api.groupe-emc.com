<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220518210359 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // $this->addSql('ALTER TABLE sell ADD company_id INT NOT NULL, DROP company');
        // $this->addSql('ALTER TABLE sell ADD CONSTRAINT FK_9B9ED07D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        // $this->addSql('CREATE INDEX IDX_9B9ED07D979B1AD6 ON sell (company_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // $this->addSql('ALTER TABLE sell DROP FOREIGN KEY FK_9B9ED07D979B1AD6');
        // $this->addSql('DROP INDEX IDX_9B9ED07D979B1AD6 ON sell');
        // $this->addSql('ALTER TABLE sell ADD company VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP company_id');
    }
}
