<?php

namespace App\Controller;

use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/api/customers-by-sector")
 */
class CustomerSectorController extends AbstractController
{
    private CustomerRepository $customerRepository;
    private Security $security;

    public function __construct(CustomerRepository $customerRepository, Security $security)
    {
        $this->customerRepository = $customerRepository;
        $this->security = $security;
    }

    /**
     * @Route("/{postalCode}", name="customers_by_sector", methods={"GET"})
     */
    public function getCustomersBySector(string $postalCode, Request $request): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user || !$user->isEnabled()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        // Rechercher les clients par code postal
        $customers = $this->customerRepository->createQueryBuilder('c')
            ->where('c.clientCode = :postalCode')
            ->setParameter('postalCode', $postalCode)
            ->orderBy('c.lastname', 'ASC')
            ->addOrderBy('c.firstname', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($customers as $customer) {
            $result[] = [
                'id' => $customer->getId(),
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
                'address' => $customer->getAddress(),
                'postcode' => $customer->getPostcode(),
                'city' => $customer->getCity(),
                'clientCode' => $customer->getClientCode(),
                'phone' => $customer->getPhone(),
                'mobile' => $customer->getMobile(),
                'email' => $customer->getEmail()
            ];
        }

        return new JsonResponse([
            'postalCode' => $postalCode,
            'customers' => $result,
            'count' => count($result)
        ]);
    }

    /**
     * @Route("/stats", name="customers_sectors_stats", methods={"GET"})
     */
    public function getSectorStats(): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user || !$user->isEnabled()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        // Statistiques des secteurs (codes postaux)
        $stats = $this->customerRepository->createQueryBuilder('c')
            ->select('c.clientCode as sector, COUNT(c.id) as customerCount')
            ->where('c.clientCode IS NOT NULL')
            ->andWhere('c.clientCode != :empty')
            ->setParameter('empty', '')
            ->groupBy('c.clientCode')
            ->orderBy('customerCount', 'DESC')
            ->getQuery()
            ->getResult();

        return new JsonResponse([
            'sectors' => $stats,
            'totalSectors' => count($stats)
        ]);
    }
}