<?php

namespace App\Controller;

use App\Entity\Sell;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/api/sell/ownership")
 */
class SellOwnershipController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * @Route("/{id}/change-owner", name="sell_change_owner", methods={"PATCH"})
     */
    public function changeOwner(Request $request, int $id): JsonResponse
    {
        // Seuls les ADMIN et SUPER_ADMIN peuvent modifier le créateur d'une vente
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé. Seuls les administrateurs peuvent modifier le créateur d\'une vente.'], 403);
        }

        $sell = $this->entityManager->getRepository(Sell::class)->find($id);
        if (!$sell) {
            return new JsonResponse(['error' => 'Vente non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['newUserId'])) {
            return new JsonResponse(['error' => 'ID du nouveau créateur requis'], 400);
        }

        $newUser = $this->entityManager->getRepository(User::class)->find($data['newUserId']);
        if (!$newUser) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        if (!$newUser->getEnabled()) {
            return new JsonResponse(['error' => 'L\'utilisateur sélectionné est désactivé'], 400);
        }

        // Vérifier que le nouvel utilisateur a un rôle commercial
        $userRoles = $newUser->getRoles();
        $commercialRoles = ['ROLE_SALES', 'ROLE_SUPER_SALES', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

        if (!array_intersect($userRoles, $commercialRoles)) {
            return new JsonResponse(['error' => 'L\'utilisateur sélectionné n\'a pas de rôle commercial'], 400);
        }

        try {
            $oldUser = $sell->getUser();
            $oldUserId = $oldUser ? $oldUser->getId() : null;
            $oldUserName = $oldUser ? $oldUser->getFirstname() . ' ' . $oldUser->getLastname() : 'Aucun';

            // Changement du créateur principal
            $sell->setUser($newUser);

            // Ajouter un commentaire dans l'historique pour traçabilité
            $currentUser = $this->security->getUser();
            $currentUserName = $currentUser ? $currentUser->getFirstname() . ' ' . $currentUser->getLastname() : 'Système';
            $newUserName = $newUser->getFirstname() . ' ' . $newUser->getLastname();

            $changeComment = sprintf(
                "[%s] Changement de créateur: %s → %s (par %s)",
                (new \DateTime())->format('Y-m-d H:i'),
                $oldUserName,
                $newUserName,
                $currentUserName
            );

            $existingComment = $sell->getComment();
            $sell->setComment($existingComment ? $existingComment . "\n" . $changeComment : $changeComment);

            // Optionnel: gérer l'ancien créateur comme commercial additionnel
            if (isset($data['keepAsAdditional']) && $data['keepAsAdditional'] && $oldUser) {
                $additionalSellers = $sell->getAdditionalSellers() ?? [];

                // Vérifier qu'il n'est pas déjà dans la liste
                $alreadyAdditional = false;
                foreach ($additionalSellers as $additional) {
                    if (isset($additional['id']) && $additional['id'] == $oldUserId) {
                        $alreadyAdditional = true;
                        break;
                    }
                }

                if (!$alreadyAdditional) {
                    $additionalSellers[] = [
                        'id' => $oldUserId,
                        'firstname' => $oldUser->getFirstname(),
                        'lastname' => $oldUser->getLastname(),
                        'addedAt' => (new \DateTime())->format('Y-m-d H:i:s')
                    ];
                    $sell->setAdditionalSellers($additionalSellers);
                }
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Créateur de la vente modifié avec succès',
                'oldOwner' => [
                    'id' => $oldUserId,
                    'name' => $oldUserName
                ],
                'newOwner' => [
                    'id' => $newUser->getId(),
                    'name' => $newUserName
                ],
                'changedBy' => $currentUserName,
                'changedAt' => (new \DateTime())->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la modification',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @Route("/{id}/ownership-history", name="sell_ownership_history", methods={"GET"})
     */
    public function getOwnershipHistory(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $sell = $this->entityManager->getRepository(Sell::class)->find($id);
        if (!$sell) {
            return new JsonResponse(['error' => 'Vente non trouvée'], 404);
        }

        // Parser les commentaires pour extraire l'historique des changements
        $comments = $sell->getComment() ?? '';
        $history = [];

        if (preg_match_all('/\[([^\]]+)\] Changement de créateur: (.+) → (.+) \(par (.+)\)/', $comments, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $history[] = [
                    'date' => $match[1],
                    'fromUser' => $match[2],
                    'toUser' => $match[3],
                    'changedBy' => $match[4]
                ];
            }
        }

        return new JsonResponse([
            'sellId' => $id,
            'currentOwner' => [
                'id' => $sell->getUser()->getId(),
                'name' => $sell->getUser()->getFirstname() . ' ' . $sell->getUser()->getLastname()
            ],
            'history' => $history,
            'additionalSellers' => $sell->getAdditionalSellers() ?? []
        ]);
    }

    /**
     * @Route("/available-users", name="sell_available_owners", methods={"GET"})
     */
    public function getAvailableOwners(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        // Récupérer tous les utilisateurs avec rôles commerciaux activés
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.enabled = true')
            ->andWhere('u.roles LIKE :sales OR u.roles LIKE :superSales OR u.roles LIKE :admin OR u.roles LIKE :superAdmin')
            ->setParameter('sales', '%ROLE_SALES%')
            ->setParameter('superSales', '%ROLE_SUPER_SALES%')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->setParameter('superAdmin', '%ROLE_SUPER_ADMIN%')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC');

        $users = $qb->getQuery()->getResult();

        $availableUsers = [];
        foreach ($users as $user) {
            $availableUsers[] = [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'profilePicture' => $user->getProfilePicture()
            ];
        }

        return new JsonResponse($availableUsers);
    }
}