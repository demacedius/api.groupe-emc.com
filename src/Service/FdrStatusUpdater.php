<?php

namespace App\Service;

use App\Entity\Sell;
use Doctrine\ORM\EntityManagerInterface;

class FdrStatusUpdater
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Check and update FDR status to "En attente pose" for sells older than 15 days
     * This method can be called from controllers or other services
     */
    public function updateExpiredFdrStatus(): int
    {
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
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
        }

        return $updatedCount;
    }

    /**
     * Check if a specific sell should be updated from FDR to "En attente pose"
     */
    public function checkAndUpdateSingleSell(Sell $sell): bool
    {
        if ($sell->getStatus() !== 'En attente FDR') {
            return false;
        }

        $createdDate = $sell->getCreatedDate();
        $limitDate = new \DateTime('-15 days');

        if ($createdDate <= $limitDate) {
            $sell->setStatus('En attente pose');
            $this->entityManager->persist($sell);
            $this->entityManager->flush();
            return true;
        }

        return false;
    }
}