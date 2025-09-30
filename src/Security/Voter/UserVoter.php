<?php 

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class UserVoter extends Voter
{
    private $security = null;
    private $em;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    public function supports($attribute, $subject)
    {
        return $attribute == 'IS_ALLOWED' && $subject instanceof \App\Entity\User;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof UserInterface) {
            return false;
        }

        // Si l'utilisateur courant est SUPER_ADMIN, il peut tout faire
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Si l'utilisateur courant est ADMIN
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return $this->canAdminModifyUser($subject);
        }

        // Pour tous les autres rôles, refuser l'accès
        return false;
    }

    /**
     * Vérifie si un ADMIN peut modifier/créer/supprimer l'utilisateur cible
     */
    private function canAdminModifyUser(User $targetUser): bool
    {
        // Les ADMIN ne peuvent pas modifier/créer/supprimer des utilisateurs qui ont le rôle SUPER_ADMIN
        if (in_array('ROLE_SUPER_ADMIN', $targetUser->getRoles())) {
            return false;
        }

        // Les ADMIN peuvent modifier tous les autres utilisateurs (ROLE_ADMIN, ROLE_SALES, ROLE_SUPER_SALES)
        return true;
    }
}