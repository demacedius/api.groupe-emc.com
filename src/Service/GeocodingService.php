<?php

namespace App\Service;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GeocodingService
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Geocode a customer's address using free OpenStreetMap Nominatim API
     */
    public function geocodeCustomer(Customer $customer): bool
    {
        $address = $this->formatAddress($customer);

        if (empty($address)) {
            $this->logger->warning('Cannot geocode customer - empty address', [
                'customerId' => $customer->getId(),
                'clientCode' => $customer->getClientCode()
            ]);
            return false;
        }

        try {
            // Using OpenStreetMap Nominatim API (free, no API key required)
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'fr', // Restrict to France
                ],
                'headers' => [
                    'User-Agent' => 'EMX-Groupe-CRM/1.0'
                ],
                'timeout' => 10
            ]);

            $data = $response->toArray();

            if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
                $customer->setLatitude($data[0]['lat']);
                $customer->setLongitude($data[0]['lon']);

                $this->entityManager->persist($customer);
                $this->entityManager->flush();

                $this->logger->info('Customer geocoded successfully', [
                    'customerId' => $customer->getId(),
                    'clientCode' => $customer->getClientCode(),
                    'latitude' => $data[0]['lat'],
                    'longitude' => $data[0]['lon']
                ]);

                return true;
            } else {
                $this->logger->warning('No geocoding results found', [
                    'customerId' => $customer->getId(),
                    'address' => $address
                ]);
                return false;
            }

        } catch (\Exception $e) {
            $this->logger->error('Geocoding failed', [
                'customerId' => $customer->getId(),
                'address' => $address,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Batch geocode multiple customers with rate limiting
     */
    public function geocodeCustomers(array $customers, int $delayMs = 1000): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($customers as $customer) {
            if ($this->geocodeCustomer($customer)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'customerId' => $customer->getId(),
                    'clientCode' => $customer->getClientCode(),
                    'reason' => 'Geocoding failed or no results found'
                ];
            }

            // Rate limiting to respect Nominatim usage policy
            if ($delayMs > 0) {
                usleep($delayMs * 1000); // Convert ms to microseconds
            }
        }

        return $results;
    }

    /**
     * Format customer address for geocoding
     */
    private function formatAddress(Customer $customer): string
    {
        $addressParts = array_filter([
            $customer->getAddress(),
            $customer->getPostcode(),
            $customer->getCity()
        ]);

        return implode(', ', $addressParts);
    }

    /**
     * Validate coordinates are within France bounds (approximate)
     */
    public function isValidFrenchCoordinates(?string $latitude, ?string $longitude): bool
    {
        if ($latitude === null || $longitude === null) {
            return false;
        }

        $lat = (float) $latitude;
        $lon = (float) $longitude;

        // Approximate bounds for France (including overseas territories would need adjustment)
        return (
            $lat >= 41.0 && $lat <= 51.5 &&  // Latitude bounds
            $lon >= -5.5 && $lon <= 10.0      // Longitude bounds
        );
    }
}