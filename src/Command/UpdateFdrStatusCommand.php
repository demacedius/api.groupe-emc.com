<?php

namespace App\Command;

use App\Entity\Sell;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-fdr-status',
    description: 'Update FDR status to "En attente pose" after 15 days',
)]
class UpdateFdrStatusCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Find all sells with "En attente FDR" status older than 15 days
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('s')
           ->from(Sell::class, 's')
           ->where('s.status = :status')
           ->andWhere('s.created_date <= :limitDate')
           ->setParameter('status', 'En attente FDR')
           ->setParameter('limitDate', new \DateTime('-15 days'));

        $sells = $qb->getQuery()->getResult();

        $updatedCount = 0;

        foreach ($sells as $sell) {
            $sell->setStatus('En attente pose');
            $this->entityManager->persist($sell);
            $updatedCount++;

            $io->writeln(sprintf(
                'Updated sell #%d (created %s) from "En attente FDR" to "En attente pose"',
                $sell->getId(),
                $sell->getCreatedDate()->format('Y-m-d')
            ));
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Updated %d sells from FDR to "En attente pose" status.', $updatedCount));
        } else {
            $io->info('No sells found to update.');
        }

        return Command::SUCCESS;
    }
}