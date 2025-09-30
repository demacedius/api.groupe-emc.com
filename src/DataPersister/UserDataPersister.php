<?php

namespace App\DataPersister;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserDataPersister implements ContextAwareDataPersisterInterface
{
    private $security = null;
    private $entityManager;
    private $userPasswordEncoder;

    public function __construct(
        EntityManagerInterface $entityManager, 
        UserPasswordEncoderInterface $userPasswordEncoder,
        Security $security
    )
    {
        $this->entityManager = $entityManager;
        $this->userPasswordEncoder = $userPasswordEncoder;
        $this->security = $security;
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof User;
    }
    
    /**
     * @param User $data
     */
    public function persist($data, array $context = [])
    {
        if ($data instanceof User) {
            if (
                ($context['item_operation_name'] ?? null) === 'patch' &&
                $this->security->isGranted('ROLE_ADMIN') &&
                !$this->security->isGranted('ROLE_SUPER_ADMIN') &&
                in_array('ROLE_SUPER_ADMIN', $data->getRoles(), true)
            ) {
                throw new AccessDeniedException();
            }

            $roles = $data->getRoles();
            $managerRoles = ['ROLE_PROFIL_B', 'ROLE_SUPER_SALES'];
            $isManager = !empty(array_intersect($roles, $managerRoles));
            $isSales = in_array('ROLE_SALES', $roles, true);

            if (
                ($context['collection_operation_name'] ?? null) === 'post' ||
                ($context['graphql_operation_name'] ?? null) === 'create'
            ) {
                $data->setPassword(
                    $this->userPasswordEncoder->encodePassword($data, bin2hex(random_bytes(20)))
                );
                $expDate = new \DateTime();
                $expDate->add(new \DateInterval('PT30M'));
                $data->setResetExp($expDate);

                $token = bin2hex(random_bytes(20));
                $data->setResetToken($token);
            }

            if ($isManager) {
                $isCreation = ($context['collection_operation_name'] ?? null) === 'post';
                if ($isCreation && !$data->getCompany()) {
                    throw new BadRequestHttpException('Une agence doit être sélectionnée pour un manager.');
                }

                if ($data->getCompany()) {
                    foreach ($data->getManagedUsers() as $managedUser) {
                        if ($managedUser->getCompany() !== $data->getCompany()) {
                            $managedUser->setCompany($data->getCompany());
                        }
                    }
                }
            }

            if ($isSales) {
                if (!$data->getManager()) {
                    throw new BadRequestHttpException('Un commercial doit être rattaché à un manager.');
                }

                $managerCompany = $data->getManager()->getCompany();
                if ($managerCompany) {
                    $data->setCompany($managerCompany);
                }
            }

            if ($data->getPlainPassword()) {
                $data->setPassword(
                    $this->userPasswordEncoder->encodePassword($data, $data->getPlainPassword())
                );
                $data->eraseCredentials();
            }
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();
    }

    public function remove($data, array $context = [])
    {
        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }
}
