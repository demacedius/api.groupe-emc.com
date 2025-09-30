<?php

namespace App\Command;

use App\Entity\Customer;
use App\Service\GeocodingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GeocodeCustomersCommand extends Command
{
    protected static $defaultName = 'app:geocode-customers';

    private EntityManagerInterface $entityManager;
    private GeocodingService $geocodingService;

    public function __construct(EntityManagerInterface $entityManager, GeocodingService $geocodingService)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->geocodingService = $geocodingService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Geocode customers that are missing coordinates')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of customers to geocode', 10)
            ->addOption('delay', 'd', InputOption::VALUE_OPTIONAL, 'Delay between requests in milliseconds', 1500)
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force geocoding even for customers that already have coordinates')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $limit = (int) $input->getOption('limit');
        $delay = (int) $input->getOption('delay');
        $force = $input->getOption('force');

        $io->title('Géocodage des clients');

        if ($force) {
            $customers = $this->entityManager->getRepository(Customer::class)
                ->findBy(['status' => ['$ne' => 'Prospect']], ['created_date' => 'DESC'], $limit);
            $io->info(sprintf('Mode forcé activé - géocodage de %d clients', count($customers)));
        } else {
            $customers = $this->entityManager->getRepository(Customer::class)
                ->findCustomersWithoutCoordinates();
            $customers = array_slice($customers, 0, $limit);
            $io->info(sprintf('Géocodage de %d clients sans coordonnées', count($customers)));
        }

        if (empty($customers)) {
            $io->success('Aucun client à géocoder');
            return Command::SUCCESS;
        }

        $io->progressStart(count($customers));

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($customers as $customer) {
            $io->progressAdvance();

            if (!$force && $customer->hasCoordinates()) {
                continue; // Skip if already has coordinates and not forcing
            }

            if ($this->geocodingService->geocodeCustomer($customer)) {
                $results['success']++;
                $io->writeln(sprintf(
                    ' ✓ %s %s [%s] - %s, %s',
                    $customer->getFirstname(),
                    $customer->getLastname(),
                    $customer->getClientCode() ?? 'N/A',
                    $customer->getLatitude(),
                    $customer->getLongitude()
                ));
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'id' => $customer->getId(),
                    'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                    'clientCode' => $customer->getClientCode()
                ];
                $io->writeln(sprintf(
                    ' ✗ %s %s [%s] - Échec',
                    $customer->getFirstname(),
                    $customer->getLastname(),
                    $customer->getClientCode() ?? 'N/A'
                ));
            }

            // Rate limiting
            if ($delay > 0) {
                usleep($delay * 1000);
            }
        }

        $io->progressFinish();

        $io->success(sprintf(
            'Géocodage terminé : %d succès, %d échecs',
            $results['success'],
            $results['failed']
        ));

        if (!empty($results['errors'])) {
            $io->section('Échecs de géocodage');
            foreach ($results['errors'] as $error) {
                $io->writeln(sprintf('- %s [%s]', $error['name'], $error['clientCode'] ?? 'N/A'));
            }
        }

        $remainingCustomers = $this->entityManager->getRepository(Customer::class)
            ->findCustomersWithoutCoordinates();

        if (!empty($remainingCustomers)) {
            $io->note(sprintf('%d clients restent sans coordonnées', count($remainingCustomers)));
        }

        return Command::SUCCESS;
    }
}