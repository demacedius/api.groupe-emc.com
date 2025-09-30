<?php

namespace App\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Entity\Sell;
use App\Entity\Customer;
use App\Entity\Appointment;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProfilBUsersStatisticsController
{
    private $security;

    public function __construct(private ManagerRegistry $doctrine, Security $security)
    {
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

        if (!$this->security->isGranted('ROLE_PROFIL_B')) {
            throw new AccessDeniedException("Accès refusé - PROFIL B requis");
        }

        // Récupérer l'agence du manager PROFIL B
        $managerCompany = $user->getCompany();

        if (!$managerCompany) {
            return new JsonResponse([]);
        }

        // Récupérer tous les utilisateurs de l'agence
        $affiliatedUsers = $em->getRepository(User::class)->findBy(['company' => $managerCompany]);
        $affiliatedUserIds = array_map(fn($u) => $u->getId(), $affiliatedUsers);

        // Filtrer pour ne garder que les commerciaux
        $commercialUsers = array_filter($affiliatedUsers, function($u) {
            return in_array('ROLE_SALES', $u->getRoles()) || in_array('ROLE_SUPER_SALES', $u->getRoles());
        });

        if (empty($commercialUsers)) {
            return new JsonResponse([
                'users' => [],
                'totals' => [
                    'caR1Ref' => 0,
                    'caVA' => 0,
                    'nbVentes' => 0
                ]
            ]);
        }

        $users = [];
        $totalCaR1Ref = 0;
        $totalCaVA = 0;
        $totalNombreVentes = 0;

        foreach ($commercialUsers as $commercialUser) {
            $stats = $this->calculateUserStatistics($commercialUser->getId());
            
            $userData = [
                'id' => $commercialUser->getId(),
                'firstname' => $commercialUser->getFirstname(),
                'lastname' => $commercialUser->getLastname(),
                'email' => $commercialUser->getEmail(),
                'profilePicture' => $commercialUser->getImageUrl(),
                'revenueR1' => $stats['revenueR1'],
                'revenueREF' => $stats['revenueREF'],
                'revenueVA' => $stats['revenueVA'],
                'countR1' => $stats['countR1'],
                'countREF' => $stats['countREF'],
                'countVA' => $stats['countVA'],
                'entryRate' => $stats['entryRate'],
                'transformationRate' => $stats['transformationRate'],
                'cancelPercent' => $stats['cancelPercent'],
                'totalSalesCount' => $stats['totalSalesCount'],
                'averageCart' => $stats['averageCart']
            ];
            
            $users[] = $userData;
            $totalCaR1Ref += $stats['revenueR1'] + $stats['revenueREF'];
            $totalCaVA += $stats['revenueVA'];
            $totalNombreVentes += $stats['countR1'] + $stats['countREF'];
        }

        // Trier par CA R1+REF décroissant
        usort($users, function($a, $b) {
            return ($b['revenueR1'] + $b['revenueREF']) <=> ($a['revenueR1'] + $a['revenueREF']);
        });

        $data = [
            'users' => $users,
            'totals' => [
                'caR1Ref' => $totalCaR1Ref,
                'caVA' => $totalCaVA,
                'nbVentes' => $totalNombreVentes
            ]
        ];

        return new JsonResponse($data);
    }

    private function calculateUserStatistics($userId) {
        $em = $this->doctrine->getManager();
        
        // Utiliser le dernier mois avec des ventes comme référence
        $sellRepo = $em->getRepository(Sell::class);
        $lastMonth = $sellRepo->getLastMonthWithSales();
        
        // Récupérer les ventes du commercial pour le mois courant
        $sales = $sellRepo->findCurrentMonthSalesByUser($userId, $lastMonth['month'], $lastMonth['year']);
        
        // Récupérer les prospects du commercial pour le mois courant
        $appointmentRepo = $em->getRepository(Appointment::class);
        $appointments = $appointmentRepo->findCurrentMonthAppointmentsByUser($userId, $lastMonth['month'], $lastMonth['year']);

        $revenueR1 = 0;
        $revenueREF = 0;
        $revenueVA = 0;
        $countR1 = 0;
        $countREF = 0;
        $countVA = 0;
        $cancelledSales = 0;
        $totalRealisedSales = 0;
        $revenueForAverageCart = 0; // R1 + REF uniquement
        $countSalesForAverageCart = 0;

        foreach ($sales as $sell) {
            if ($sell->getStatus() == "Annulée") {
                $cancelledSales++;
            } else {
                $totalRealisedSales++;
                
                if ($sell->getSource() == "VA") {
                    $revenueVA += $sell->getTotal();
                    $countVA++;
                } elseif ($sell->getSource() == "R1") {
                    $revenueR1 += $sell->getTotal();
                    $revenueForAverageCart += $sell->getTotal();
                    $countSalesForAverageCart++;
                    
                    // Vérifier si c'est une vente binôme
                    $commercialCount = 0;
                    if ($sell->getUser()) $commercialCount++;
                    if ($sell->getAdditionnalSeller()) $commercialCount++;
                    
                    if ($commercialCount > 1) {
                        $countR1 += 0.5; // Vente binôme = 0.5 point
                    } else {
                        $countR1++; // Vente normale = 1 point
                    }
                    
                } elseif ($sell->getSource() == "REF") {
                    $revenueREF += $sell->getTotal();
                    $revenueForAverageCart += $sell->getTotal();
                    $countREF++;
                    $countSalesForAverageCart++;
                }
            }
        }

        // Calcul du pourcentage d'annulation
        $cancelPercent = 0;
        if ($totalRealisedSales > 0) {
            $cancelPercent = (($cancelledSales * 100) / $totalRealisedSales);
        }

        // Calcul du panier moyen (R1 + REF uniquement)
        $averageCart = 0;
        if ($countSalesForAverageCart > 0) {
            $averageCart = ($revenueForAverageCart / $countSalesForAverageCart);
        }

        // Statistiques des prospects
        $countTransformed = 0;
        $countAppointments = count($appointments);
        $entreeSansSuite = 0;
        $transformationRate = 0;
        $entryRate = 0;

        foreach ($appointments as $appointment) {
            if ($appointment->getStatus() === "Vente") {
                $countTransformed++;
            }
            if ($appointment->getStatus() === "Entrée sans suite") {
                $entreeSansSuite++;
            }
            if ($appointment->getStatus() === "A venir") {
                $countAppointments--; // Ne pas compter les prospects "A venir"
            }
        }

        if ($countAppointments > 0) {
            $transformationRate = (($countTransformed * 100) / $countAppointments);
            $entryRate = (($entreeSansSuite * 100) / $countAppointments);
        }

        return [
            'revenueR1' => $revenueR1,
            'revenueREF' => $revenueREF,
            'revenueVA' => $revenueVA,
            'countR1' => $countR1,
            'countREF' => $countREF,
            'countVA' => $countVA,
            'cancelPercent' => $cancelPercent,
            'averageCart' => $averageCart,
            'transformationRate' => $transformationRate,
            'entryRate' => $entryRate,
            'totalSalesCount' => $countR1 + $countREF + $countVA
        ];
    }
}