<?php

namespace App\Command;

use App\Entity\Customer;
use App\Service\PostalCodeExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-client-codes',
    description: 'Met à jour les codes clients basés sur les codes postaux extraits des adresses',
)]
class UpdateClientCodesCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private PostalCodeExtractor $postalCodeExtractor;

    public function __construct(EntityManagerInterface $entityManager, PostalCodeExtractor $postalCodeExtractor)
    {
        $this->entityManager = $entityManager;
        $this->postalCodeExtractor = $postalCodeExtractor;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Mise à jour des codes clients');

        // Récupérer tous les clients sans code client ou avec adresse modifiée
        $customers = $this->entityManager->getRepository(Customer::class)
            ->createQueryBuilder('c')
            ->where('c.clientCode IS NULL OR c.clientCode = :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getResult();

        $io->progressStart(count($customers));
        $updated = 0;

        foreach ($customers as $customer) {
            $address = $customer->getAddress();
            $postcode = $customer->getPostcode();
            $extractedPostalCode = null;

            // Essayer d'extraire de l'adresse d'abord
            if ($address) {
                $extractedPostalCode = $this->postalCodeExtractor->extractFromAddress($address);
            }

            // Si pas trouvé dans l'adresse, utiliser le champ postcode existant comme fallback
            if (!$extractedPostalCode && $postcode && $this->postalCodeExtractor->isValidFrenchPostalCode($postcode)) {
                $extractedPostalCode = $postcode;
            }

            if ($extractedPostalCode) {
                $customer->setClientCode($extractedPostalCode);
                $updated++;
            }

            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success(sprintf('%d codes clients mis à jour sur %d clients traités.', $updated, count($customers)));

        return Command::SUCCESS;
    }
}