<?php

namespace App\Doctrine;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Sell;
use App\Entity\User;
use App\Entity\Appointment;
use App\Entity\Customer;
use App\Service\FdrStatusUpdater;

class SellListener
{
    private $security;
    private $entityManager;
    private $fdrStatusUpdater;

    public function __construct(
        EntityManagerInterface $entityManager, 
        Security $security, 
        FdrStatusUpdater $fdrStatusUpdater
    ) {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->fdrStatusUpdater = $fdrStatusUpdater;
    }

    public function prePersist(Sell $sell) {
        $currentUser = $this->security->getUser();
        
        if (!$currentUser instanceof User) {
            throw new AccessDeniedException();
        }

        if ($sell->getUser()) {
            return;
        }

        $sell->setUser($currentUser);

        $number = $sell->getCompany()->getNextSellId();
        $sell->setSellId($number);
    }

    public function postPersist(Sell $sell) {
        $currentUser = $this->security->getUser();

        $id = $sell->getSellId();
        $id++;

        $company = $sell->getCompany();
        $company->setNextSellId($id);
        $this->entityManager->persist($company);

        if ($this->security->isGranted('ROLE_SALES')) {
            $customer = $sell->getCustomer();
            $appointment = $this->entityManager->getRepository(Appointment::class)->findTodayAppointment($currentUser, $customer);
            
            if ($appointment) {
                $appointment->setStatus("Vente");
                $appointment->setClosed(true);
                $this->entityManager->persist($appointment);

                if ($customer->getStatus() === "Prospect") {
                    $customer->setStatus("En cours");
                    $this->entityManager->persist($customer);
                }

            }
        }
        
        $this->entityManager->flush();
    }

    public function preUpdate(Sell $sell) {
        // Store original status to compare in postUpdate
        $uow = $this->entityManager->getUnitOfWork();
        $changeset = $uow->getEntityChangeSet($sell);
        
        if (isset($changeset['status'])) {
            $sell->_originalStatus = $changeset['status'][0]; // Store original status
        }
    }

    public function postUpdate(Sell $sell) {
        // Sync sell status to customer status
        $this->syncSellStatusToCustomer($sell);
        $this->entityManager->flush();
    }

    public function postLoad(Sell $sell) {
        // Auto-check and update FDR status when loading sell entities
        // Temporarily disabled to avoid circular dependency
        // $this->fdrStatusUpdater->checkAndUpdateSingleSell($sell);
    }

    private function syncSellStatusToCustomer(Sell $sell): void {
        $customer = $sell->getCustomer();
        if (!$customer) {
            return;
        }

        $sellStatus = $sell->getStatus();
        $newCustomerStatus = null;

        // Map sell status to customer status according to business rules
        switch ($sellStatus) {
            case 'Encaissée':
                $newCustomerStatus = 'Encaissé';
                break;
            case 'Annulée':
                $newCustomerStatus = 'Annulé';
                break;
            case 'Impayé':
                $newCustomerStatus = 'Impayé';
                break;
            case 'Black List':
                $newCustomerStatus = 'Black List';
                break;
            case 'En attente FDR':
            case 'Dossier incomplet':
            case 'VENTE A REVOIR':
            case 'En attente pose':
            case 'En attente paiement':
                $newCustomerStatus = 'En cours';
                break;
            default:
                // Don't change customer status for other sell statuses
                return;
        }

        if ($newCustomerStatus && $customer->getStatus() !== $newCustomerStatus) {
            $customer->setStatus($newCustomerStatus);
            $this->entityManager->persist($customer);
        }
    }
}