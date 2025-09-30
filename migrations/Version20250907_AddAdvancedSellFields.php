<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter les nouveaux champs avancés à la table sell
 * - Remise commerciale (montant et type)
 * - Dates FDR et encaissement
 * - Montants acompte et solde
 * - Commerciaux supplémentaires
 * - Commentaire statut personnalisé
 * - Désactivation frais de dossier
 */
final class Version20250907_AddAdvancedSellFields extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les champs avancés pour les ventes: remise commerciale, dates FDR/encaissement, montants, commerciaux supplémentaires, commentaire statut personnalisé';
    }

    public function up(Schema $schema): void
    {
        // Nouveaux champs pour la table sell
        $table = $schema->getTable('sell');

        if (!$table->hasColumn('discount_amount')) {
            $this->addSql('ALTER TABLE sell ADD discount_amount DECIMAL(10, 3) DEFAULT NULL');
        }

        if (!$table->hasColumn('discount_type')) {
            $this->addSql('ALTER TABLE sell ADD discount_type VARCHAR(20) DEFAULT NULL');
        }

        if (!$table->hasColumn('encashment_date')) {
            $this->addSql('ALTER TABLE sell ADD encashment_date DATETIME DEFAULT NULL');
        }

        if (!$table->hasColumn('fdr_date')) {
            $this->addSql('ALTER TABLE sell ADD fdr_date DATETIME DEFAULT NULL');
        }

        if (!$table->hasColumn('deposit_amount')) {
            $this->addSql('ALTER TABLE sell ADD deposit_amount DECIMAL(10, 3) DEFAULT NULL');
        }

        if (!$table->hasColumn('balance_amount')) {
            $this->addSql('ALTER TABLE sell ADD balance_amount DECIMAL(10, 3) DEFAULT NULL');
        }

        if (!$table->hasColumn('additional_sellers')) {
            $this->addSql('ALTER TABLE sell ADD additional_sellers JSON DEFAULT NULL');
        }

        if (!$table->hasColumn('custom_status_comment')) {
            $this->addSql('ALTER TABLE sell ADD custom_status_comment VARCHAR(255) DEFAULT NULL');
        }

        if (!$table->hasColumn('file_fees_disabled')) {
            $this->addSql('ALTER TABLE sell ADD file_fees_disabled TINYINT(1) DEFAULT 0 NOT NULL');
        }
        
        // Index pour améliorer les performances
        if (!$table->hasIndex('IDX_SELL_ENCASHMENT_DATE')) {
            $this->addSql('CREATE INDEX IDX_SELL_ENCASHMENT_DATE ON sell (encashment_date)');
        }

        if (!$table->hasIndex('IDX_SELL_FDR_DATE')) {
            $this->addSql('CREATE INDEX IDX_SELL_FDR_DATE ON sell (fdr_date)');
        }

        if (!$table->hasIndex('IDX_SELL_DISCOUNT_TYPE')) {
            $this->addSql('CREATE INDEX IDX_SELL_DISCOUNT_TYPE ON sell (discount_type)');
        }
    }

    public function down(Schema $schema): void
    {
        // Suppression des index
        $this->addSql('DROP INDEX IDX_SELL_ENCASHMENT_DATE ON sell');
        $this->addSql('DROP INDEX IDX_SELL_FDR_DATE ON sell');
        $this->addSql('DROP INDEX IDX_SELL_DISCOUNT_TYPE ON sell');
        
        // Suppression des colonnes
        $this->addSql('ALTER TABLE sell DROP discount_amount');
        $this->addSql('ALTER TABLE sell DROP discount_type');
        $this->addSql('ALTER TABLE sell DROP encashment_date');
        $this->addSql('ALTER TABLE sell DROP fdr_date');
        $this->addSql('ALTER TABLE sell DROP deposit_amount');
        $this->addSql('ALTER TABLE sell DROP balance_amount');
        $this->addSql('ALTER TABLE sell DROP additional_sellers');
        $this->addSql('ALTER TABLE sell DROP custom_status_comment');
        $this->addSql('ALTER TABLE sell DROP file_fees_disabled');
    }
}
