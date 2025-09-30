<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Customer;

class FusionCustomersController
{
    private $security;

    public function __construct(private ManagerRegistry $doctrine, Security $security)
    {
        $this->security = $security;
    }

    public function __invoke(Request $request) {
        $em = $this->doctrine->getManager();
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedException("Accès refusé");
        }

        if (!$user->getEnabled()) {
            throw new AccessDeniedException("Accès refusé");
        }

        if (
            !$this->security->isGranted('ROLE_SUPER_ADMIN') &&
            !$this->security->isGranted('ROLE_ADMIN') &&
            !$this->security->isGranted('ROLE_SUPER_SALES')
        ) {
            throw new AccessDeniedException("Accès refusé");
        }

        $content = json_decode($request->getContent(), true);
        
        if (!$content || !is_array($content) || !array_key_exists('ids', $content)) {
            throw new BadRequestHttpException();
        }

        $ids = explode(',', $content['ids']);

        if (!$ids || !is_array($ids) || count($ids) < 2) {
            throw new BadRequestHttpException();
        }

        $mainCustomer = null;
        $customers = [];

        foreach ($ids as $id) {
            $customer = $em->getRepository(Customer::class)->find(intval($id));

            if (!$customer) {
                throw new BadRequestHttpException();
            }

            if ($customer->getFlag()) {
                // can't have multiple main customer
                if ($mainCustomer) {
                    throw new BadRequestHttpException('Veuillez choisir un seul client favori.');
                }

                $mainCustomer = $customer;
            } else {
                $customers[] = $customer;
            }
        }
        
        if (!$mainCustomer) {
            throw new BadRequestHttpException('Veuillez choisir un client favori.');
        }

        foreach ($customers as $customer) {
            foreach ($customer->getSales() as $sell) {
                $sell->setCustomer($mainCustomer);
                $em->persist($sell);
                $em->flush();
            }
            foreach ($customer->getAppointments() as $appointment) {
                $appointment->setCustomer($mainCustomer);
                $em->persist($appointment);
                $em->flush();
            }
            $em->remove($customer);
            $em->flush();
        }

        return new Response(
            'Clients fusionnés avec succès',
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
    }
}