<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220505201255 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE sell ADD company VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE sell_item ADD sell_id INT NOT NULL');
        $this->addSql('ALTER TABLE sell_item ADD CONSTRAINT FK_100EC9ACE745F4 FOREIGN KEY (sell_id) REFERENCES sell (id)');
        $this->addSql('CREATE INDEX IDX_100EC9ACE745F4 ON sell_item (sell_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE sell DROP company');
        $this->addSql('ALTER TABLE sell_item DROP FOREIGN KEY FK_100EC9ACE745F4');
        $this->addSql('DROP INDEX IDX_100EC9ACE745F4 ON sell_item');
        $this->addSql('ALTER TABLE sell_item DROP sell_id');
    }
}
