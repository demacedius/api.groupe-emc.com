<?php

namespace App\Controller;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Entity\Sell;
use App\Entity\Appointment;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Company;

class UsersStatisticsController
{
    private $security;

    public function __construct(private ManagerRegistry $doctrine, Security $security) {
        $this->security = $security;
    }

    public function __invoke() {
        $em = $this->doctrine->getManager();
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedException("Accès refusé");
        }

        if (!$user->getEnabled()) {
            throw new AccessDeniedException("Accès refusé");
        }

        $users = array();

        // Récupérer les utilisateurs commerciaux activés
        // Note: La spécification demande "tous les utilisateurs avec accès autorisé" pour PROFIL A+
        // ce qui correspond à tous les commerciaux activés avec les bons rôles
        $salesUsers = $em->getRepository(User::class)->findEnabledUsersByRole('ROLE_SALES');
        $superSalesUsers = $em->getRepository(User::class)->findEnabledUsersByRole('ROLE_SUPER_SALES');
        $salesUsers = array_merge($salesUsers, $superSalesUsers);

        foreach ($salesUsers as $salesUser) {
            // Récupérer toutes les ventes de l'année au lieu du mois courant seulement
            $sales = $em->getRepository(Sell::class)->findBy(['user' => $salesUser->getId()]);
            $revenue = 0;
            $revenueR1 = 0;
            $revenueREF = 0;
            $revenueVA = 0;
            $R1 = 0;
            $REF = 0;
            $VA = 0; // Add VA counter
            $cancelled = 0;
            $cancelPercent = 0;
            $averageCart = 0;
            $countSales = count($sales);
            $totalSalesCount = 0; // Initialize total sales count

            if ($countSales > 0) {
                foreach ($sales as $sell) {
                    if ($sell->getStatus() == "Annulée") {
                        $cancelled++;
                    } else {
                        switch($sell->getSource()) {
                            case "R1":
                                if ($sell->getAdditionnalSeller()) {
                                    $R1 += 0.5;
                                } else {
                                    $R1++;
                                }
                                $revenueR1 += $this->getRevenueFromFinancialData($sell);
                                $revenue += $this->getRevenueFromFinancialData($sell);
                                break;
                                
                            case "REF":
                                $REF++;
                                $revenueREF += $this->getRevenueFromFinancialData($sell);
                                $revenue += $this->getRevenueFromFinancialData($sell);
                                break;
    
                            case "VA":
                                $VA++; // Count VA sales
                                $revenueVA += $this->getPriceFromFinancialData($sell);
                                break;
    
                            default:
                                break;
                        }
                        $totalSalesCount++; // Increment total sales count for non-cancelled sales
                    }
                }
                // Formule correcte: (ventes annulées / total des ventes) x 100
                if ($countSales > 0) {
                    $cancelPercent = ($cancelled * 100) / $countSales;
                } else {
                    $cancelPercent = 0;
                }
                // Update averageCart calculation here after the loop
                if (($R1 + $REF) > 0) { // Avoid division by zero
                    $averageCart = ($revenueR1 + $revenueREF) / ($R1 + $REF);
                } else {
                    $averageCart = 0;
                }
            }

            // Récupérer tous les rendez-vous de l'année au lieu du mois courant seulement
            $appointments = $em->getRepository(Appointment::class)->findBy(['user' => $salesUser->getId()]);
            $countEntered = 0;
            $countTransformed = 0;
            $transformationRate = 0;    
            $entryRate = 0;    
            $countAppointments = count($appointments);

            if ($countAppointments > 0) {
                foreach ($appointments as $appointment) {
                    switch($appointment->getStatus()) {
                        case "Vente":
                            $countTransformed++;
                            break;

                        case "Entrée sans suite":
                            $countEntered++;
                            break;

                        case "A venir":
                            $countAppointments--;
                            break;

                        default:
                            break;
                    }
                }

                if ($countTransformed > 0) {
                    $transformationRate = (($countTransformed * 100) / $countAppointments);
                }
    
                if ($countEntered > 0) {
                    $entryRate = (($countEntered * 100) / $countAppointments);
                }
            }

            $users[] = array(
                "firstname" => $salesUser->getFirstname(),
                "lastname" => $salesUser->getLastname(),
                "profilePicture" => $salesUser->getProfilePicture(),
                "revenue" => $revenue,
                "revenueR1" => $revenueR1,
                "revenueREF" => $revenueREF,
                "revenueVA" => $revenueVA,
                "countR1" => $R1,
                "countREF" => $REF,
                "transformationRate" => $transformationRate,
                "entryRate" => $entryRate,
                "cancelPercent" => $cancelPercent,
                "averageCart" => $averageCart,
                "countVA" => $VA, // Add VA count
                "totalSalesCount" => $R1 + $REF + $VA // Total of all sales (R1 + REF + VA)
            );
        }

        usort($users, function($a, $b) { return ($a['revenueR1'] + $a['revenueREF']) < ($b['revenueR1'] + $b['revenueREF']); });

        // Calculer les 3 totaux demandés
        $totalCaR1Ref = 0;
        $totalCaVA = 0;
        $totalNombreVentes = 0;
        
        foreach ($users as $user) {
            $totalCaR1Ref += ($user['revenueR1'] + $user['revenueREF']);
            $totalCaVA += $user['revenueVA'];
            $totalNombreVentes += ($user['countR1'] + $user['countREF']); // R1 + REF seulement
        }

        $data = [
            'users' => $users,
            'totals' => [
                'caR1Ref' => $totalCaR1Ref,    // 1ere : TOTAL CA R1 + CA REF
                'caVA' => $totalCaVA,           // 2eme : TOTAL CA VA
                'nbVentes' => $totalNombreVentes // 3eme : Nombre de vente (= R1+ REF)
            ]
        ];

        return new JsonResponse($data);
    }

    public function getRevenue($sell) {
        if ($sell->getSource() == "VA") {
            return 0;
        }
        if (!$sell->getAdditionnalSeller()) {
            return $sell->getTotal();
        } else {
            return round(($sell->getTotal() / 2), 2);
        }
    }

    /**
     * Récupère le prix depuis le financialSection au lieu du total calculé
     * Solution au problème: le champ "total" est inconsistant (vide pour comptant, rempli pour financement)
     */
    private function getPriceFromFinancialData($sell): float {
        $financialSection = $sell->getFinancialSection();
        if (!$financialSection || !is_array($financialSection)) {
            // Fallback sur getTotal() si pas de financialSection
            return floatval($sell->getTotal());
        }

        // Le financialSection est déjà un array, pas besoin de JSON decode
        return floatval($financialSection['price'] ?? $sell->getTotal());
    }

    /**
     * Calcule le revenu en utilisant le prix depuis financial_data
     * et applique la règle de partage pour les ventes en binôme
     */
    private function getRevenueFromFinancialData($sell): float {
        if ($sell->getSource() == "VA") {
            return 0.0;
        }

        $price = $this->getPriceFromFinancialData($sell);

        if (!$sell->getAdditionnalSeller()) {
            return $price;
        } else {
            return round(($price / 2), 2);
        }
    }
}