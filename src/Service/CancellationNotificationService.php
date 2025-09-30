<?php

namespace App\Service;

use App\Entity\Sell;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class CancellationNotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    /**
     * Vérifie si le commercial peut encore voir les ventes annulées (1 mois)
     */
    public function canSeeAnnulation(Sell $sell, User $user): bool
    {
        if ($sell->getStatus() !== 'Annulée') {
            return true;
        }

        // Les ADMIN et SUPER_ADMIN voient toujours tout
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Pour les commerciaux, vérifier la règle du 1 mois
        if ($sell->getUser() && $sell->getUser()->getId() === $user->getId()) {
            $cancellationDate = $sell->getCancellationDate() ?: $sell->getUpdatedAt();
            if ($cancellationDate) {
                $oneMonthAgo = new \DateTime('-1 month');
                return $cancellationDate > $oneMonthAgo;
            }
        }

        return false;
    }

    /**
     * Marque une vente comme annulée avec notification au commercial
     */
    public function cancelSell(Sell $sell, string $reason = null): void
    {
        $previousStatus = $sell->getStatus();
        $sell->setStatus('Annulée');
        $sell->setCancellationDate(new \DateTime());
        $sell->setUpdatedAt(new \DateTime());
        
        if ($reason) {
            $sell->setCustomStatusComment("Annulée: " . $reason);
        }

        // Créer une notification pour le commercial
        if ($sell->getUser()) {
            $this->createCancellationNotification($sell, $previousStatus);
        }

        $this->entityManager->persist($sell);
        $this->entityManager->flush();
    }

    /**
     * Récupère les ventes annulées visibles pour un commercial
     */
    public function getVisibleCancelledSells(User $user): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $qb->select('s')
           ->from(Sell::class, 's')
           ->where('s.status = :status')
           ->andWhere('s.user = :user')
           ->setParameter('status', 'Annulée')
           ->setParameter('user', $user);

        // Si ce n'est pas un admin, filtrer par date (1 mois)
        if (!$this->security->isGranted('ROLE_ADMIN') && !$this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $oneMonthAgo = new \DateTime('-1 month');
            $qb->andWhere('(s.cancellationDate IS NULL AND s.updatedAt > :oneMonthAgo) OR s.cancellationDate > :oneMonthAgo')
               ->setParameter('oneMonthAgo', $oneMonthAgo);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Signale l'annulation au commercial
     */
    private function createCancellationNotification(Sell $sell, string $previousStatus): void
    {
        // Ici on pourrait implémenter un système de notifications plus sophistiqué
        // Pour l'instant, on utilise le commentaire personnalisé
        $currentComment = $sell->getCustomStatusComment() ?: '';
        $notificationMessage = "Vente passée de '$previousStatus' à 'Annulée' le " . (new \DateTime())->format('d/m/Y H:i');
        
        if ($currentComment) {
            $sell->setCustomStatusComment($currentComment . " | " . $notificationMessage);
        } else {
            $sell->setCustomStatusComment($notificationMessage);
        }
    }

    /**
     * Nettoie les anciennes notifications d'annulation (plus de 1 mois)
     */
    public function cleanupOldCancellations(): int
    {
        $oneMonthAgo = new \DateTime('-1 month');
        
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(Sell::class, 's')
           ->set('s.customStatusComment', 'NULL')
           ->where('s.status = :status')
           ->andWhere('s.cancellationDate < :oneMonthAgo')
           ->andWhere('s.customStatusComment LIKE :notification')
           ->setParameter('status', 'Annulée')
           ->setParameter('oneMonthAgo', $oneMonthAgo)
           ->setParameter('notification', '%Vente passée de%');

        return $qb->getQuery()->execute();
    }
}