<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Service\GeocodingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/api/geocoding")
 */
class GeocodingController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private Security $security;
    private GeocodingService $geocodingService;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security,
        GeocodingService $geocodingService
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->geocodingService = $geocodingService;
    }

    /**
     * @Route("/customer/{id}", name="geocode_single_customer", methods={"POST"})
     */
    public function geocodeSingleCustomer(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user || !$user->getEnabled()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        // Only admins and super admins can geocode
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $customer = $this->entityManager->getRepository(Customer::class)->find($id);
        if (!$customer) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        if ($customer->hasCoordinates()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le client a déjà des coordonnées',
                'coordinates' => [
                    'latitude' => $customer->getLatitude(),
                    'longitude' => $customer->getLongitude()
                ]
            ]);
        }

        $success = $this->geocodingService->geocodeCustomer($customer);

        if ($success) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Client géolocalisé avec succès',
                'coordinates' => [
                    'latitude' => $customer->getLatitude(),
                    'longitude' => $customer->getLongitude()
                ]
            ]);
        } else {
            return new JsonResponse([
                'success' => false,
                'message' => 'Échec de la géolocalisation. Vérifiez l\'adresse du client.'
            ], 400);
        }
    }

    /**
     * @Route("/batch", name="geocode_batch_customers", methods={"POST"})
     */
    public function geocodeBatchCustomers(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user || !$user->getEnabled()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        // Only admins and super admins can batch geocode
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $limit = $data['limit'] ?? 10; // Default to 10 customers to avoid overwhelming the API
        $delayMs = $data['delay'] ?? 1500; // 1.5 second delay between requests

        if ($limit > 50) {
            return new JsonResponse([
                'error' => 'Limite maximale de 50 clients par batch pour respecter les limitations de l\'API'
            ], 400);
        }

        $customersWithoutCoordinates = $this->entityManager
            ->getRepository(Customer::class)
            ->findCustomersWithoutCoordinates();

        $customersToGeocode = array_slice($customersWithoutCoordinates, 0, $limit);

        if (empty($customersToGeocode)) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Aucun client sans coordonnées trouvé',
                'results' => [
                    'success' => 0,
                    'failed' => 0,
                    'errors' => []
                ]
            ]);
        }

        $results = $this->geocodingService->geocodeCustomers($customersToGeocode, $delayMs);

        return new JsonResponse([
            'success' => true,
            'message' => sprintf(
                'Géolocalisation terminée: %d succès, %d échecs sur %d clients traités',
                $results['success'],
                $results['failed'],
                count($customersToGeocode)
            ),
            'results' => $results,
            'remaining' => count($customersWithoutCoordinates) - count($customersToGeocode)
        ]);
    }

    /**
     * @Route("/update-coordinates/{id}", name="update_customer_coordinates", methods={"PATCH"})
     */
    public function updateCustomerCoordinates(int $id, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user || !$user->getEnabled()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        // Only admins and super admins can manually update coordinates
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $customer = $this->entityManager->getRepository(Customer::class)->find($id);
        if (!$customer) {
            return new JsonResponse(['error' => 'Client non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;

        if ($latitude === null || $longitude === null) {
            return new JsonResponse([
                'error' => 'Latitude et longitude sont obligatoires'
            ], 400);
        }

        // Validate coordinates format
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return new JsonResponse([
                'error' => 'Latitude et longitude doivent être des nombres valides'
            ], 400);
        }

        // Validate coordinates are reasonable for France
        if (!$this->geocodingService->isValidFrenchCoordinates($latitude, $longitude)) {
            return new JsonResponse([
                'error' => 'Les coordonnées semblent être en dehors de la France'
            ], 400);
        }

        $customer->setLatitude($latitude);
        $customer->setLongitude($longitude);

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Coordonnées mises à jour avec succès',
            'coordinates' => [
                'latitude' => $customer->getLatitude(),
                'longitude' => $customer->getLongitude()
            ]
        ]);
    }
}