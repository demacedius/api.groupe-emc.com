<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\Customer;

final class CustomersByUserDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
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
        return Customer::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        $currentUser = $this->security->getUser();

        if (!$currentUser) {
            throw new AccessDeniedException();
        }

        if (
            $this->security->isGranted('ROLE_SUPER_ADMIN') ||
            $this->security->isGranted('ROLE_ADMIN')
        ) {
            $query = $this->requestStack->getCurrentRequest()->query;
            $type = $query->get('type');
            if ($type == 'prospect') {
                $results = $this->em->getRepository(Customer::class)->findAllProspects();
            } else {
                $results = $this->em->getRepository(Customer::class)->findAllNotProspects();
            }
            return $results;
        }

        if ($this->security->isGranted('ROLE_SALES') || $this->security->isGranted('ROLE_SUPER_SALES')) {
            $query = $this->requestStack->getCurrentRequest()->query;
            $type = $query->get('type');
            if ($type == 'prospect') {
                $results = $this->em->getRepository(Customer::class)->findAllProspectsByUser($currentUser->getId());
            } else {
                $results = $this->em->getRepository(Customer::class)->findAllNotProspectsByUser($currentUser->getId());
            }
            return $results;
        }

        if ($this->security->isGranted('ROLE_PROFIL_B')) {
            $query = $this->requestStack->getCurrentRequest()->query;
            $type = $query->get('type');
            if ($type == 'prospect') {
                // Pour PROFIL_B, récupérer les prospects des utilisateurs gérés
                $managedUserIds = array_map(fn($u) => $u->getId(), $currentUser->getManagedUsers()->toArray());
                $managedUserIds[] = $currentUser->getId(); // Inclure soi-même
                $results = $this->em->getRepository(Customer::class)->findAllProspectsByUsers($managedUserIds);
            } else {
                $results = $this->em->getRepository(Customer::class)->findAllNotProspects();
            }
            return $results;
        }

        throw new AccessDeniedException();
    }
}