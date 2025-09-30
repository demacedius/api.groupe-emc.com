<?php 

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Sell;
use App\Entity\User;

class SellVoter extends Voter
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
        return $attribute == 'IS_ALLOWED' && $subject instanceof \App\Entity\Sell;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        if (
            $this->security->isGranted('ROLE_SALES') &&
            (
                $subject->getUser() == $user ||
                $subject->getAdditionnalSeller() == $user
            ) &&
            in_array($subject->getStatus(), Sell::INPROGRESS)
        ) {
            return true;
        }

        if (
            $this->security->isGranted('ROLE_SUPER_SALES') &&
            in_array($subject->getStatus(), Sell::NOTPAID)
        ) {
            return true;
        }

        // Les managers PROFIL_B peuvent modifier les ventes de leur agence
        if (
            $this->security->isGranted('ROLE_PROFIL_B') &&
            $subject->getCompany() == $user->getCompany()
        ) {
            return true;
        }

        return false;
    }
}