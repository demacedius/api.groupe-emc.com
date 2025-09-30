<?php

namespace App\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Entity\Sell;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProfilBSalesController
{
    private $security;

    public function __construct(private ManagerRegistry $doctrine, Security $security)
    {
        $this->security = $security;
    }

    public function __invoke() {
        $em = $this->doctrine->getManager();
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedException("Accès refusé");
        }

        if (!$user->getEnabled()) {
            throw new AccessDeniedException("Accès refusé");
        }

        if (!$this->security->isGranted('ROLE_PROFIL_B')) {
            throw new AccessDeniedException("Accès refusé - PROFIL B requis");
        }

        // Récupérer l'agence du manager PROFIL B
        $managerCompany = $user->getCompany();

        if (!$managerCompany) {
            return new JsonResponse([]);
        }

        // Récupérer toutes les ventes de l'agence
        $sellRepo = $em->getRepository(Sell::class);
        $qb = $sellRepo->createQueryBuilder('s')
            ->leftJoin('s.customer', 'c')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.additionnalSeller', 'as')
            ->leftJoin('s.company', 'comp')
            ->where('s.company = :company')
            ->setParameter('company', $managerCompany)
            ->orderBy('s.id', 'DESC');

        $sales = $qb->getQuery()->getResult();

        // Sérializer les ventes avec les données nécessaires
        $serializedSales = [];
        foreach ($sales as $sell) {
            $serializedSales[] = [
                'id' => $sell->getId(),
                'sellId' => $sell->getSellId(),
                'created_date' => $sell->getCreatedDate()->format('d/m/Y H:i'),
                'status' => $sell->getStatus(),
                'total' => $sell->getTotal(),
                'source' => $sell->getSource(),
                'paymentType' => $sell->getPaymentType(),
                'encashmentDate' => $sell->getEncashmentDate() ? $sell->getEncashmentDate()->format('Y-m-d') : null,
                'customStatusComment' => $sell->getCustomStatusComment(),
                'customer' => [
                    'id' => $sell->getCustomer()->getId(),
                    'firstname' => $sell->getCustomer()->getFirstname(),
                    'lastname' => $sell->getCustomer()->getLastname(),
                    'flag' => $sell->getCustomer()->getFlag()
                ],
                'user' => [
                    'id' => $sell->getUser()->getId(),
                    'firstname' => $sell->getUser()->getFirstname(),
                    'lastname' => $sell->getUser()->getLastname()
                ],
                'additionnalSeller' => $sell->getAdditionnalSeller() ? [
                    'id' => $sell->getAdditionnalSeller()->getId(),
                    'firstname' => $sell->getAdditionnalSeller()->getFirstname(),
                    'lastname' => $sell->getAdditionnalSeller()->getLastname()
                ] : null,
                'company' => [
                    'id' => $sell->getCompany()->getId(),
                    'name' => $sell->getCompany()->getName(),
                    'prefix' => $sell->getCompany()->getPrefix()
                ],
                'teleoperatorName' => $sell->getTeleoperatorName()
            ];
        }

        return new JsonResponse($serializedSales);
    }
}