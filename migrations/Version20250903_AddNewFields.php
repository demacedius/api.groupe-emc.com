<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout des nouveaux champs demandés dans les spécifications
 */
final class Version20250903_AddNewFields extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des nouveaux champs pour les entités Customer, Sell et Appointment';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si les colonnes existent déjà avant de les ajouter
        
        // Customer: Ajouter clientCode s'il n'existe pas déjà
        if (!$schema->getTable('customer')->hasColumn('client_code')) {
            $this->addSql('ALTER TABLE customer ADD client_code VARCHAR(50) DEFAULT NULL');
        }
        
        // Customer: Ajouter secondMobile et secondEmail
        if (!$schema->getTable('customer')->hasColumn('second_mobile')) {
            $this->addSql('ALTER TABLE customer ADD second_mobile VARCHAR(255) DEFAULT NULL');
        }
        
        if (!$schema->getTable('customer')->hasColumn('second_email')) {
            $this->addSql('ALTER TABLE customer ADD second_email VARCHAR(255) DEFAULT NULL');
        }
        
        // Sell: Ajouter les champs pour les remises et autres fonctionnalités
        if (!$schema->getTable('sell')->hasColumn('discount_amount')) {
            $this->addSql('ALTER TABLE sell ADD discount_amount DECIMAL(10, 3) DEFAULT NULL');
        }
        
        if (!$schema->getTable('sell')->hasColumn('discount_type')) {
            $this->addSql('ALTER TABLE sell ADD discount_type VARCHAR(20) DEFAULT NULL COMMENT \'percent or amount\'');
        }
        
        if (!$schema->getTable('sell')->hasColumn('encashment_date')) {
            $this->addSql('ALTER TABLE sell ADD encashment_date DATETIME DEFAULT NULL');
        }
        
        if (!$schema->getTable('sell')->hasColumn('fdr_date')) {
            $this->addSql('ALTER TABLE sell ADD fdr_date DATETIME DEFAULT NULL');
        }
        
        if (!$schema->getTable('sell')->hasColumn('deposit_amount')) {
            $this->addSql('ALTER TABLE sell ADD deposit_amount DECIMAL(10, 3) DEFAULT NULL');
        }
        
        if (!$schema->getTable('sell')->hasColumn('balance_amount')) {
            $this->addSql('ALTER TABLE sell ADD balance_amount DECIMAL(10, 3) DEFAULT NULL');
        }
        
        // Sell: Ajouter la relation pour plusieurs vendeurs additionnels (VA)
        if (!$schema->getTable('sell')->hasColumn('additional_sellers')) {
            $this->addSql('ALTER TABLE sell ADD additional_sellers JSON DEFAULT NULL COMMENT \'For VA sales with multiple sellers\'');
        }
        
        // Appointment: Les champs appointmentDate et teleoperatorName existent déjà
        // Vérifier qu'ils sont bien présents
        if (!$schema->getTable('appointment')->hasColumn('appointment_date')) {
            $this->addSql('ALTER TABLE appointment ADD appointment_date DATETIME DEFAULT NULL');
        }
        
        if (!$schema->getTable('appointment')->hasColumn('teleoperator_name')) {
            $this->addSql('ALTER TABLE appointment ADD teleoperator_name VARCHAR(255) DEFAULT NULL');
        }
        
        // Ajouter les index pour améliorer les performances
        if (!$schema->getTable('customer')->hasIndex('IDX_CUSTOMER_CLIENT_CODE')) {
            $this->addSql('CREATE INDEX IDX_CUSTOMER_CLIENT_CODE ON customer (client_code)');
        }

        if (!$schema->getTable('sell')->hasIndex('IDX_SELL_ENCASHMENT_DATE')) {
            $this->addSql('CREATE INDEX IDX_SELL_ENCASHMENT_DATE ON sell (encashment_date)');
        }

        if (!$schema->getTable('sell')->hasIndex('IDX_SELL_FDR_DATE')) {
            $this->addSql('CREATE INDEX IDX_SELL_FDR_DATE ON sell (fdr_date)');
        }

        if (!$schema->getTable('appointment')->hasIndex('IDX_APPOINTMENT_DATE')) {
            $this->addSql('CREATE INDEX IDX_APPOINTMENT_DATE ON appointment (appointment_date)');
        }
    }

    public function down(Schema $schema): void
    {
        // Supprimer les index
        if ($schema->getTable('customer')->hasIndex('IDX_CUSTOMER_CLIENT_CODE')) {
            $this->addSql('DROP INDEX IDX_CUSTOMER_CLIENT_CODE ON customer');
        }

        if ($schema->getTable('sell')->hasIndex('IDX_SELL_ENCASHMENT_DATE')) {
            $this->addSql('DROP INDEX IDX_SELL_ENCASHMENT_DATE ON sell');
        }

        if ($schema->getTable('sell')->hasIndex('IDX_SELL_FDR_DATE')) {
            $this->addSql('DROP INDEX IDX_SELL_FDR_DATE ON sell');
        }

        if ($schema->getTable('appointment')->hasIndex('IDX_APPOINTMENT_DATE')) {
            $this->addSql('DROP INDEX IDX_APPOINTMENT_DATE ON appointment');
        }
        
        // Supprimer les colonnes ajoutées
        $this->addSql('ALTER TABLE customer DROP client_code, DROP second_mobile, DROP second_email');
        $this->addSql('ALTER TABLE sell DROP discount_amount, DROP discount_type, DROP encashment_date, DROP fdr_date, DROP deposit_amount, DROP balance_amount, DROP additional_sellers');
        // Note: appointmentDate et teleoperatorName ne sont pas supprimés car ils existaient déjà
    }
}
