<?php 

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Sell;
use App\Entity\SellItem;

class SellItemVoter extends Voter
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
        return $attribute == 'IS_ALLOWED' && $subject instanceof \App\Entity\SellItem;
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
                $subject->getSell()->getUser() == $user ||
                $subject->getSell()->getAdditionnalSeller() == $user
            ) &&
            in_array($subject->getSell()->getStatus(), Sell::INPROGRESS)
        ) {
            return true;
        }

        // Les managers PROFIL_B peuvent modifier les items des ventes de leur agence
        if (
            $this->security->isGranted('ROLE_PROFIL_B') &&
            $subject->getSell()->getCompany() == $user->getCompany()
        ) {
            return true;
        }

        return false;
    }
}