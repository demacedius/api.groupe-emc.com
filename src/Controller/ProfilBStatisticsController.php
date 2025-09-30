<?php

namespace App\Controller;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Sell;
use App\Entity\Customer;
use App\Entity\Appointment;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProfilBStatisticsController
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

        // Trouver le dernier mois avec des ventes de l'agence
        $qb = $em->createQueryBuilder();
        $lastSale = $qb->select('s')
            ->from('App\Entity\Sell', 's')
            ->where('s.company = :company')
            ->setParameter('company', $managerCompany)
            ->orderBy('s.created_date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        
        if ($lastSale && $lastSale->getCreatedDate()) {
            try {
                $lastSaleDate = $lastSale->getCreatedDate();
                if ($lastSaleDate instanceof \DateTime) {
                    $currentMonth = (int)$lastSaleDate->format('n');
                    $currentYear = (int)$lastSaleDate->format('Y');
                } else {
                    $dateTime = new \DateTime($lastSaleDate);
                    $currentMonth = (int)$dateTime->format('n');
                    $currentYear = (int)$dateTime->format('Y');
                }
            } catch (\Exception $e) {
                $currentMonth = 12;
                $currentYear = 2024;
            }
        } else {
            $currentMonth = 12;
            $currentYear = 2024;
        }
        
        $lastMonth = $currentMonth - 1;
        $lastMonthYear = $currentYear;
        
        if ($lastMonth == 0) {
            $lastMonth = 12;
            $lastMonthYear = $currentYear - 1;
        }

        // Filtrer les données par agence
        $currentSalesData = $this->getSalesDataByCompany($managerCompany, $currentMonth, $currentYear);
        $lastMonthSalesData = $this->getSalesDataByCompany($managerCompany, $lastMonth, $lastMonthYear);

        // Calculer les progressions pour les entités (filtré par agence)
        $entityStatsWithProgress = array();
        if (isset($currentSalesData["entityStats"])) {
            foreach ($currentSalesData["entityStats"] as $entity => $stats) {
                $lastMonthEntity = $lastMonthSalesData["entityStats"][$entity] ?? array('caTotal' => 0, 'caVA' => 0);
                $entityStatsWithProgress[$entity] = array(
                    "caTotal" => $stats['caTotal'],
                    "caVA" => $stats['caVA'],
                    "change" => $this->getProgress($stats['caTotal'], $lastMonthEntity['caTotal'])
                );
            }
        }

        $data = array(
            "salesCount" => array(
                "current" => $currentSalesData["salesCount"],
                "progress" => $this->getProgress($currentSalesData["salesCount"], $lastMonthSalesData["salesCount"])
            ),
            "customersTotal" => array(
                "current" => $currentSalesData["customersCount"],
                "progress" => $this->getProgress($currentSalesData["customersCount"], $lastMonthSalesData["customersCount"])
            ),
            "revenue" => array(
                "current" => $currentSalesData["revenue"],
                "progress" => $this->getProgress($currentSalesData["revenue"], $lastMonthSalesData["revenue"])
            ),
            "revenueVA" => array(
                "current" => $currentSalesData["revenueVA"],
                "progress" => $this->getProgress($currentSalesData["revenueVA"], $lastMonthSalesData["revenueVA"])
            ),
            "cancelPercent" => array(
                "current" => $currentSalesData["cancelPercent"],
                "progress" => $this->getProgress($currentSalesData["cancelPercent"], $lastMonthSalesData["cancelPercent"])
            ),
            "averageCart" => array(
                "current" => $currentSalesData["averageCart"],
                "progress" => $this->getProgress($currentSalesData["averageCart"], $lastMonthSalesData["averageCart"])
            ),
            "transformationRate" => array(
                "current" => $currentSalesData["transformationRate"],
                "progress" => $this->getProgress($currentSalesData["transformationRate"], $lastMonthSalesData["transformationRate"])
            ),
            "entryRate" => array(
                "current" => $currentSalesData["entryRate"],
                "progress" => $this->getProgress($currentSalesData["entryRate"], $lastMonthSalesData["entryRate"])
            ),
            "entityStats" => $entityStatsWithProgress,
            "affiliatedUsers" => $this->getCompanyUsers($managerCompany)
        );

        return new JsonResponse($data);
    }

    public function getProgress($val1, $val2) {
        if ($val2 === 0) {
            return 0;
        }
        $val1 = floatval($val1);
        $val2 = floatval($val2);
        return (($val1 - $val2) / $val2) * 100;
    }

    private function getCompanyUsers($company) {
        $em = $this->doctrine->getManager();
        $users = $em->getRepository(User::class)->findBy(['company' => $company]);

        return array_map(function($u) {
            return [
                'id' => $u->getId(),
                'firstname' => $u->getFirstname(),
                'lastname' => $u->getLastname(),
                'email' => $u->getEmail()
            ];
        }, $users);
    }

    public function getSalesDataByCompany($company, $month = null, $year = null) {
        $em = $this->doctrine->getManager();

        // Filtrer les ventes par agence
        if ($month !== null && $year !== null) {
            try {
                $startDate = new \DateTime("$year-$month-01");
                $endDate = clone $startDate;
                $endDate->modify('last day of this month')->setTime(23, 59, 59);

                $qb = $em->getRepository(Sell::class)->createQueryBuilder('s');
                $sales = $qb->where('s.company = :company')
                    ->andWhere('s.created_date BETWEEN :start AND :end')
                    ->setParameter('company', $company)
                    ->setParameter('start', $startDate)
                    ->setParameter('end', $endDate)
                    ->getQuery()
                    ->getResult();
            } catch (\Exception $e) {
                $qb = $em->getRepository(Sell::class)->createQueryBuilder('s');
                $sales = $qb->where('s.company = :company')
                    ->setParameter('company', $company)
                    ->getQuery()
                    ->getResult();
            }
        } else {
            $qb = $em->getRepository(Sell::class)->createQueryBuilder('s');
            $sales = $qb->where('s.company = :company')
                ->setParameter('company', $company)
                ->getQuery()
                ->getResult();
        }

        $customerIds = array();
        $r1CustomerIds = array();
        $revenue = 0;
        $revenueVA = 0;
        $revenueR1 = 0;
        $revenueREF = 0;
        $cancelled = 0;
        $cancelPercent = 0;
        $averageCart = 0;
        $countSales = 0;
        $countSalesR1 = 0;
        $countSalesR1Binome = 0;
        $countSalesREF = 0;
        $countSalesVA = 0;
        $countSalesForAverageCart = 0;
        $cancelledSales = 0;
        $totalRealisedSales = 0;
        
        // Entity-based statistics (FP, 3M, PH, MP)
        $entityStats = [
            'FP' => ['caTotal' => 0, 'caVA' => 0],
            '3M' => ['caTotal' => 0, 'caVA' => 0],
            'PH' => ['caTotal' => 0, 'caVA' => 0],
            'MP' => ['caTotal' => 0, 'caVA' => 0]
        ];

        foreach ($sales as $sell) {
            if ($sell->getStatus() == "Annulée") {
                $cancelledSales++;
            } else {
                $totalRealisedSales++;
                $countSales++;
                
                if ($sell->getCustomer() && !in_array($sell->getCustomer()->getId(), $customerIds)) {
                    $customerIds[] = $sell->getCustomer()->getId();
                }
                
                $entity = $this->determineEntity($sell);

                if ($sell->getSource() == "VA") {
                    $revenueVA += $sell->getTotal();
                    $countSalesVA++;
                    if (isset($entityStats[$entity])) {
                        $entityStats[$entity]['caVA'] += $sell->getTotal();
                    }
                } else { // R1 and REF
                    $revenue += $sell->getTotal();
                    $countSalesForAverageCart++;
                    
                    if (isset($entityStats[$entity])) {
                        $entityStats[$entity]['caTotal'] += $sell->getTotal();
                    }
                    
                    if ($sell->getSource() == "R1") {
                        $revenueR1 += $sell->getTotal();
                        
                        // Vérifier si c'est une vente binôme
                        $commercialCount = 0;
                        if ($sell->getUser()) $commercialCount++;
                        if ($sell->getAdditionnalSeller()) $commercialCount++;
                        
                        if ($commercialCount > 1) {
                            $countSalesR1Binome++;
                            $countSalesR1 += 0.5;
                        } else {
                            $countSalesR1++;
                        }
                        
                        if ($sell->getCustomer() && !in_array($sell->getCustomer()->getId(), $r1CustomerIds)) {
                            $r1CustomerIds[] = $sell->getCustomer()->getId();
                        }
                    } elseif ($sell->getSource() == "REF") {
                        $revenueREF += $sell->getTotal();
                        $countSalesREF++;
                    }
                }
            }
        }
        
        // Formule annulation : (ventes annulées sur ventes réalisées) × 100
        if ($totalRealisedSales > 0) {
            $cancelPercent = (($cancelledSales * 100) / $totalRealisedSales);
        }
        if ($countSalesForAverageCart > 0) {
            $averageCart = ($revenue / $countSalesForAverageCart);
        }

        // Filtrer les appointments par utilisateurs affiliés
        if ($month !== null && $year !== null) {
            try {
                $startDate = new \DateTime("$year-$month-01");
                $endDate = clone $startDate;
                $endDate->modify('last day of this month')->setTime(23, 59, 59);
                
                $qb = $em->getRepository(Appointment::class)->createQueryBuilder('a');
                $appointments = $qb->leftJoin('a.user', 'u')
                    ->where('u.company = :company')
                    ->andWhere('a.createdDate BETWEEN :start AND :end')
                    ->setParameter('company', $company)
                    ->setParameter('start', $startDate)
                    ->setParameter('end', $endDate)
                    ->getQuery()
                    ->getResult();
            } catch (\Exception $e) {
                $qb = $em->getRepository(Appointment::class)->createQueryBuilder('a');
                $appointments = $qb->leftJoin('a.user', 'u')
                    ->where('u.company = :company')
                    ->setParameter('company', $company)
                    ->getQuery()
                    ->getResult();
            }
        } else {
            $qb = $em->getRepository(Appointment::class)->createQueryBuilder('a');
            $appointments = $qb->leftJoin('a.user', 'u')
                ->where('u.company = :company')
                ->setParameter('company', $company)
                ->getQuery()
                ->getResult();
        }

        $countTransformed = 0;
        $countAppointments = count($appointments);
        $transformationRate = 0;
        $entryRate = 0;
        $entreeSansSuite = 0;

        if ($countAppointments > 0) {
            foreach($appointments as $appointment) {
                if ($appointment->getStatus() === "Vente") {
                    $countTransformed++;
                }
                if ($appointment->getStatus() === "Entrée sans suite") {
                    $entreeSansSuite++;
                }
                if ($appointment->getStatus() === "A venir") {
                    $countAppointments--;
                }
            }
        }

        if ($countTransformed > 0 && $countAppointments > 0) {
            $transformationRate = (($countTransformed * 100) / $countAppointments);
        }
        
        if ($entreeSansSuite > 0 && $countAppointments > 0) {
            $entryRate = (($entreeSansSuite * 100) / $countAppointments);
        }

        return array(
            "salesCount" => $countSales,
            "customersCount" => count($r1CustomerIds),
            "revenue" => $revenue, // CA R1 + REF
            "revenueVA" => $revenueVA,
            "revenueR1" => $revenueR1,
            "revenueREF" => $revenueREF,
            "countR1" => $countSalesR1,
            "countR1Binome" => $countSalesR1Binome,
            "countREF" => $countSalesREF,
            "countVA" => $countSalesVA,
            "cancelPercent" => $cancelPercent,
            "averageCart" => $averageCart,
            "transformationRate" => $transformationRate,
            "entryRate" => $entryRate,
            "entityStats" => $entityStats
        );
    }
    
    private function determineEntity($sell) {
        $company = $sell->getCompany();
        if (!$company) {
            return 'FP';
        }
        
        $prefix = $company->getPrefix();
        $name = $company->getName();
        
        if ($prefix === 'FP' || stripos($name, 'FPEMC') !== false || stripos($name, 'France Patrimoine') !== false) {
            return 'FP';
        } elseif ($prefix === '3M' || stripos($name, '3M') !== false) {
            return '3M';
        } elseif ($prefix === 'PH' || stripos($name, 'Patrimoine Habitat') !== false) {
            return 'PH';
        } elseif ($prefix === 'MP' || stripos($name, 'Mon Patrimoine') !== false) {
            return 'MP';
        }
        
        return $prefix ?: 'FP';
    }
}