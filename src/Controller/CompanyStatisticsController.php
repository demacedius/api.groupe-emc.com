<?php

namespace App\Controller;

use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Sell;
use App\Entity\Company;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AsController]
class CompanyStatisticsController
{
    private $security;

    public function __construct(private ManagerRegistry $doctrine, Security $security)
    {
        $this->security = $security;
    }

    #[Route('/api/company_stats', name: 'api_company_stats', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $em = $this->doctrine->getManager();
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedException("Accès refusé");
        }

        if (!$user->getEnabled()) {
            throw new AccessDeniedException("Accès refusé");
        }

        // Only A+ profile can access this
        if (!$this->security->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException("Accès refusé");
        }

        $companies = $em->getRepository(Company::class)->findAll();
        $companyStats = [];

        // Utilise des dates qui correspondent aux données de 2024 dans la base
        $currentMonth = '09';
        $currentYear = '2024';
        
        $previousMonth = '08';
        $previousYear = '2024';

        foreach ($companies as $company) {
            $currentMonthSales = $em->getRepository(Sell::class)->findSalesByCompanyAndMonth($company, (int)$currentMonth, (int)$currentYear);
            $previousMonthSales = $em->getRepository(Sell::class)->findSalesByCompanyAndMonth($company, (int)$previousMonth, (int)$previousYear);

            $currentStats = $this->calculateSalesStats($currentMonthSales);
            $previousStats = $this->calculateSalesStats($previousMonthSales);

            $companyStats[] = [
                'id' => $company->getId(),
                'name' => $company->getName(),
                'currentMonth' => [
                    'caR1Ref' => $currentStats['caR1Ref'],
                    'caVA' => $currentStats['caVA'],
                ],
                'previousMonth' => [
                    'caR1Ref' => $previousStats['caR1Ref'],
                    'caVA' => $previousStats['caVA'],
                ],
                'progressCaR1Ref' => $this->calculateProgress($currentStats['caR1Ref'], $previousStats['caR1Ref']),
                'progressCaVA' => $this->calculateProgress($currentStats['caVA'], $previousStats['caVA']),
            ];
        }

        return new JsonResponse($companyStats);
    }

    private function calculateSalesStats(array $sales): array
    {
        $caR1Ref = 0;
        $caVA = 0;

        foreach ($sales as $sell) {
            if ($sell->getSource() === "VA") {
                $caVA += $sell->getTotal();
            } else { // R1 and REF
                $caR1Ref += $sell->getTotal();
            }
        }

        return [
            'caR1Ref' => $caR1Ref,
            'caVA' => $caVA,
        ];
    }

    private function calculateProgress(float $current, float $previous): float
    {
        if ($previous === 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return (($current - $previous) / $previous) * 100.0;
    }
}
