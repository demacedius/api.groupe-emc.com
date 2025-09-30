<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\Sell;

final class SalesByUserDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $em;
    private $security;
    private $requestStack;

    public function __construct(EntityManagerInterface $em, RequestStack $requestStack, Security $security) {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Sell::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        $currentUser = $this->security->getUser();

        if (!$currentUser) {
            throw new AccessDeniedException();
        }

        if (
            $this->security->isGranted('ROLE_ADMIN') ||
            $this->security->isGranted('ROLE_SUPER_ADMIN')
        ) {
            $results = $this->em->getRepository(Sell::class)->findAll();
            return $results;
        }

        if ($this->security->isGranted('ROLE_PROFIL_B')) {
            // Pour PROFIL B, récupérer les ventes de tous les utilisateurs affiliés
            $affiliatedUsers = $currentUser->getManagedUsers()->toArray();
            $affiliatedUserIds = array_map(fn($u) => $u->getId(), $affiliatedUsers);
            
            // Ajouter l'utilisateur lui-même s'il a aussi le rôle commercial
            if (in_array('ROLE_SALES', $currentUser->getRoles()) || in_array('ROLE_SUPER_SALES', $currentUser->getRoles())) {
                $affiliatedUserIds[] = $currentUser->getId();
            }

            if (!empty($affiliatedUserIds)) {
                // Pour PROFIL B, toutes les ventes sauf celles avec statuts encaissés, black list, annulés et impayés
                $excludedStatus = array(
                    'Encaissée',
                    'Black List', 
                    'Annulée',
                    'Impayé'
                );
                $results = $this->em->getRepository(Sell::class)->findSalesByUsersExcludingStatus($affiliatedUserIds, $excludedStatus);
                return $results;
            } else {
                return [];
            }
        }

        if ($this->security->isGranted('ROLE_SALES')) {
            $allowedStatus = array(
                'En attente FDR',
                'Dossier incomplet',
                'En attente pose'
            );
            $results = $this->em->getRepository(Sell::class)->findSalesByUserAndStatus($currentUser->getId(), $allowedStatus);
            return $results;
        }

        if ($this->security->isGranted('ROLE_SUPER_SALES')) {
            $allowedStatus = array(
                'En attente FDR',
                'Dossier incomplet',
                'En attente pose',
                'En attente paiement'
            );
            $results = $this->em->getRepository(Sell::class)->findSalesByUserAndStatus($currentUser->getId(), $allowedStatus);
            return $results;
        }

        throw new AccessDeniedException();
    }
}