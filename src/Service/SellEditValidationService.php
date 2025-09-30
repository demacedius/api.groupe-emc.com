<?php

namespace App\Service;

use App\Entity\Sell;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;

class SellEditValidationService
{
    public function __construct(
        private Security $security
    ) {
    }

    /**
     * Vérifie si une vente peut être modifiée en cours de création de devis
     */
    public function canEditSellInProgress(Sell $sell): bool
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return false;
        }

        // Les SUPER_ADMIN peuvent tout modifier
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Les ADMIN peuvent modifier sauf si c'est une vente encaissée
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return !in_array($sell->getStatus(), ['Encaissée']);
        }

        // Les managers PROFIL_B peuvent modifier les ventes de leur agence
        if ($this->security->isGranted('ROLE_PROFIL_B')) {
            $userCompany = $user->getCompany();
            $sellCompany = $sell->getCompany();

            if ($userCompany && $sellCompany && $userCompany->getId() === $sellCompany->getId()) {
                return !in_array($sell->getStatus(), ['Encaissée', 'Annulée']);
            }
        }

        // Le propriétaire de la vente peut la modifier si elle n'est pas encaissée
        if ($sell->getUser() && $sell->getUser()->getId() === $user->getId()) {
            return !in_array($sell->getStatus(), ['Encaissée', 'Annulée']);
        }

        return false;
    }

    /**
     * Détermine quels champs peuvent être modifiés selon le statut et le profil
     */
    public function getEditableFields(Sell $sell): array
    {
        $user = $this->security->getUser();
        $status = $sell->getStatus();
        
        if (!$user || !$this->canEditSellInProgress($sell)) {
            return [];
        }

        $editableFields = [];

        // Champs toujours modifiables (sauf si encaissé)
        if ($status !== 'Encaissée') {
            $editableFields = [
                'customer',           // Informations client
                'services',          // Prestations 
                'discountAmount',    // Remise
                'discountType',      // Type de remise
                'paymentMethod',     // Mode de paiement
                'workDate',          // Date de travaux
                'followup',          // Suivi
                'fees',              // Frais
                'depositAmount',     // Montant acompte
                'balanceAmount',     // Montant solde
                'additionalSellers', // Commerciaux supplémentaires
                'customStatusComment', // Commentaire personnalisé
                'fileFeesDisabled',  // Frais de dossier désactivés
                'hasOfferedServices', // Prestations offertes
                'offeredServicesDescription' // Description prestations offertes
            ];
        }

        // Restrictions selon le statut
        switch ($status) {
            case 'En attente FDR':
            case 'Dossier incomplet':
            case 'VENTE A REVOIR':
                // Tous les champs modifiables
                break;
                
            case 'En attente pose':
                // Limitation des modifications après FDR
                $restrictedFields = ['customer', 'services', 'paymentMethod'];
                if (!$this->security->isGranted('ROLE_ADMIN')) {
                    $editableFields = array_diff($editableFields, $restrictedFields);
                }
                break;
                
            case 'En attente paiement':
                // Seuls quelques champs modifiables
                $editableFields = array_intersect($editableFields, [
                    'depositAmount', 'balanceAmount', 'customStatusComment'
                ]);
                break;
                
            case 'Annulée':
            case 'Encaissée':
                // Aucun champ modifiable
                $editableFields = [];
                break;
        }

        return $editableFields;
    }

    /**
     * Valide une modification en cours
     */
    public function validateEdit(Sell $sell, array $data): array
    {
        $errors = [];
        $editableFields = $this->getEditableFields($sell);

        foreach ($data as $field => $value) {
            if (!in_array($field, $editableFields)) {
                $errors[] = "Le champ '$field' ne peut pas être modifié pour ce statut de vente";
            }
        }

        // Validations spécifiques
        if (isset($data['discountAmount']) && $data['discountAmount'] < 0) {
            $errors[] = "Le montant de la remise ne peut pas être négatif";
        }

        if (isset($data['depositAmount']) && isset($data['balanceAmount'])) {
            $total = ($data['depositAmount'] ?? 0) + ($data['balanceAmount'] ?? 0);
            if ($total != $sell->getTotal()) {
                $errors[] = "La somme de l'acompte et du solde doit égaler le montant total";
            }
        }

        return $errors;
    }
}