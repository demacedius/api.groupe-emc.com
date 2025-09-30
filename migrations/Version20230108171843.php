<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230108171843 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE appointment CHANGE closed closed TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE sell ADD additionnal_seller_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sell ADD CONSTRAINT FK_9B9ED07DDD965FED FOREIGN KEY (additionnal_seller_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_9B9ED07DDD965FED ON sell (additionnal_seller_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE appointment CHANGE closed closed TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE sell DROP FOREIGN KEY FK_9B9ED07DDD965FED');
        $this->addSql('DROP INDEX IDX_9B9ED07DDD965FED ON sell');
        $this->addSql('ALTER TABLE sell DROP additionnal_seller_id');
    }
}
