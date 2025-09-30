<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\Appointment;

final class AppointmentsByUserDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
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
        return Appointment::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        $currentUser = $this->security->getUser();

        if (!$currentUser) {
            throw new AccessDeniedException();
        }

        if (
            $this->security->isGranted('ROLE_ADMIN') ||
            $this->security->isGranted('ROLE_SUPER_ADMIN') ||
            $this->security->isGranted('ROLE_SUPER_SALES')
        ) {
            $results = $this->em->getRepository(Appointment::class)->findAllOpenedAppointments();
            return $results;
        }

        if ($this->security->isGranted('ROLE_PROFIL_B')) {
            // Pour PROFIL B, récupérer les prospects de tous les utilisateurs affiliés
            $affiliatedUsers = $currentUser->getManagedUsers()->toArray();
            $affiliatedUserIds = array_map(fn($u) => $u->getId(), $affiliatedUsers);
            
            // Ajouter l'utilisateur lui-même s'il a aussi le rôle commercial
            if (in_array('ROLE_SALES', $currentUser->getRoles()) || in_array('ROLE_SUPER_SALES', $currentUser->getRoles())) {
                $affiliatedUserIds[] = $currentUser->getId();
            }

            if (!empty($affiliatedUserIds)) {
                $results = $this->em->getRepository(Appointment::class)->findAllOpenedAppointmentsByUsers($affiliatedUserIds);
                return $results;
            } else {
                return [];
            }
        }

        if ($this->security->isGranted('ROLE_SALES')) {
            $results = $this->em->getRepository(Appointment::class)->findAllOpenedAppointmentsByUser($currentUser->getId());
            return $results;
        }

        throw new AccessDeniedException();
    }
}