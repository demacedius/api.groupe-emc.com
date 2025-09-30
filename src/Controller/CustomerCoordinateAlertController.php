<?php

namespace App\Controller;

use App\Entity\Customer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/api/alerts/coordinates")
 */
class CustomerCoordinateAlertController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * @Route("/missing", name="missing_coordinates_alert", methods={"GET"})
     */
    public function getMissingCoordinatesAlert(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user || !$user->getEnabled()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        // Only admins and super admins can see coordinate alerts
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $customersWithoutCoordinates = $this->entityManager
            ->getRepository(Customer::class)
            ->findCustomersWithoutCoordinates();

        $alertData = [
            'type' => 'missing_coordinates',
            'severity' => 'warning',
            'title' => 'Clients sans coordonnées GPS',
            'message' => sprintf(
                '%d client(s) n\'ont pas de coordonnées GPS renseignées',
                count($customersWithoutCoordinates)
            ),
            'count' => count($customersWithoutCoordinates),
            'customers' => [],
            'generatedAt' => (new \DateTime())->format('Y-m-d H:i:s')
        ];

        // Include first 10 customers for preview
        foreach (array_slice($customersWithoutCoordinates, 0, 10) as $customer) {
            $alertData['customers'][] = [
                'id' => $customer->getId(),
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
                'address' => $customer->getAddress(),
                'postcode' => $customer->getPostcode(),
                'city' => $customer->getCity(),
                'clientCode' => $customer->getClientCode(),
                'createdDate' => $customer->getCreatedDate() ? $customer->getCreatedDate()->format('Y-m-d H:i') : null
            ];
        }

        return new JsonResponse($alertData);
    }

    /**
     * @Route("/missing/full-list", name="missing_coordinates_full_list", methods={"GET"})
     */
    public function getFullMissingCoordinatesList(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user || !$user->getEnabled()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        // Only admins and super admins can see coordinate alerts
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $customersWithoutCoordinates = $this->entityManager
            ->getRepository(Customer::class)
            ->findCustomersWithoutCoordinates();

        $customers = [];
        foreach ($customersWithoutCoordinates as $customer) {
            $customers[] = [
                'id' => $customer->getId(),
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
                'address' => $customer->getAddress(),
                'postcode' => $customer->getPostcode(),
                'city' => $customer->getCity(),
                'phone' => $customer->getPhone(),
                'mobile' => $customer->getMobile(),
                'email' => $customer->getEmail(),
                'clientCode' => $customer->getClientCode(),
                'status' => $customer->getStatus(),
                'createdDate' => $customer->getCreatedDate() ? $customer->getCreatedDate()->format('Y-m-d H:i') : null
            ];
        }

        return new JsonResponse([
            'total' => count($customers),
            'customers' => $customers,
            'generatedAt' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * @Route("/statistics", name="coordinates_statistics", methods={"GET"})
     */
    public function getCoordinatesStatistics(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user || !$user->getEnabled()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        // Only admins and super admins can see coordinate statistics
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $totalCustomers = $this->entityManager
            ->getRepository(Customer::class)
            ->count(['status' => ['$ne' => 'Prospect']]); // Not prospects

        $customersWithoutCoordinates = $this->entityManager
            ->getRepository(Customer::class)
            ->findCustomersWithoutCoordinates();

        $missingCount = count($customersWithoutCoordinates);
        $withCoordinatesCount = $totalCustomers - $missingCount;

        $completionRate = $totalCustomers > 0 ? round(($withCoordinatesCount / $totalCustomers) * 100, 2) : 0;

        return new JsonResponse([
            'totalCustomers' => $totalCustomers,
            'withCoordinates' => $withCoordinatesCount,
            'missingCoordinates' => $missingCount,
            'completionRate' => $completionRate,
            'alertLevel' => $completionRate < 80 ? 'high' : ($completionRate < 95 ? 'medium' : 'low'),
            'generatedAt' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }
}