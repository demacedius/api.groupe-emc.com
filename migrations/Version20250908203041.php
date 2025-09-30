<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250908203041 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE service_package (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(10, 3) NOT NULL, vat NUMERIC(5, 2) DEFAULT NULL, enabled TINYINT(1) DEFAULT \'1\' NOT NULL, sort_order INT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE service_package_services (service_package_id INT NOT NULL, service_id INT NOT NULL, INDEX IDX_30F65216621D924B (service_package_id), INDEX IDX_30F65216ED5CA9E6 (service_id), PRIMARY KEY(service_package_id, service_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE service_package_services ADD CONSTRAINT FK_30F65216621D924B FOREIGN KEY (service_package_id) REFERENCES service_package (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_package_services ADD CONSTRAINT FK_30F65216ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer DROP second_mobile, DROP second_email, CHANGE client_code client_code VARCHAR(50) DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_SELL_ENCASHMENT_DATE ON sell');
        $this->addSql('DROP INDEX IDX_SELL_FDR_DATE ON sell');
        $this->addSql('ALTER TABLE sell ADD cancellation_date DATETIME DEFAULT NULL, CHANGE discount_type discount_type VARCHAR(20) DEFAULT NULL, CHANGE additional_sellers additional_sellers JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE sell_item ADD service_package_id INT DEFAULT NULL, CHANGE service_id service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sell_item ADD CONSTRAINT FK_100EC9621D924B FOREIGN KEY (service_package_id) REFERENCES service_package (id)');
        $this->addSql('CREATE INDEX IDX_100EC9621D924B ON sell_item (service_package_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE sell_item DROP FOREIGN KEY FK_100EC9621D924B');
        $this->addSql('ALTER TABLE service_package_services DROP FOREIGN KEY FK_30F65216621D924B');
        $this->addSql('DROP TABLE service_package');
        $this->addSql('DROP TABLE service_package_services');
        $this->addSql('ALTER TABLE customer ADD second_mobile VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD second_email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE client_code client_code VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE sell DROP cancellation_date, CHANGE discount_type discount_type VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'percent or amount\', CHANGE additional_sellers additional_sellers JSON DEFAULT NULL COMMENT \'For VA sales with multiple sellers\'');
        $this->addSql('CREATE INDEX IDX_SELL_ENCASHMENT_DATE ON sell (encashment_date)');
        $this->addSql('CREATE INDEX IDX_SELL_FDR_DATE ON sell (fdr_date)');
        $this->addSql('DROP INDEX IDX_100EC9621D924B ON sell_item');
        $this->addSql('ALTER TABLE sell_item DROP service_package_id, CHANGE service_id service_id INT NOT NULL');
    }
}
