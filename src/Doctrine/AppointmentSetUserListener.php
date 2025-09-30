<?php

namespace App\Doctrine;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Appointment;
use App\Entity\User;

class AppointmentSetUserListener
{
    private $security;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, Security $security) {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function prePersist(Appointment $appointment) {
        $currentUser = $this->security->getUser();
        
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        if ($appointment->getUser()) {
            return;
        }

        $appointment->setUser($currentUser);
    }
}