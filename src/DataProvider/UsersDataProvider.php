<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\User;

final class UsersDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
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
        return User::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        $currentUser = $this->security->getUser();

        if (!$currentUser) {
            throw new AccessDeniedException();
        }

        $users = [];

        if ($context['request_uri'] === "/api/users") {
            $users = $this->em->getRepository(User::class)->findAll();
        } else if ($context['request_uri'] === "/api/additionnal-users") {
            // Pour Profile B : retourner les utilisateurs affiliés + les Profile B
            if ($this->security->isGranted('ROLE_PROFIL_B')) {
                $affiliatedUsers = $currentUser->getManagedUsers()->toArray();
                $profilBUsers = $this->em->getRepository(User::class)->findEnabledUsersByRole('ROLE_PROFIL_B');

                // Combiner sans doublons
                $users = [];
                $userIds = [];

                foreach ($affiliatedUsers as $user) {
                    if (!in_array($user->getId(), $userIds)) {
                        $users[] = $user;
                        $userIds[] = $user->getId();
                    }
                }

                foreach ($profilBUsers as $user) {
                    if (!in_array($user->getId(), $userIds)) {
                        $users[] = $user;
                        $userIds[] = $user->getId();
                    }
                }
            } else {
                // Pour les autres rôles : comportement existant
                $users = $this->em->getRepository(User::class)->findBy(array(
                    "binomialAllowed" => true,
                    "enabled" => true
                ));
            }
        }

        if ($this->security->isGranted('ROLE_SALES')) {
            $filteredUsers = array_filter($users, function($user) {
                if ($user->getId() === $this->security->getUser()->getId()) { return false; }
                return true;
            });

            return $filteredUsers;
        }
        return $users;

        // if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_SUPER_ADMIN')) {
        //     $results = $this->em->getRepository(User::class)->findAllOpenedUsersByUser($currentUser->getId());
        //     return $results;
        // }
    }
}