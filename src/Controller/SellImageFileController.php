<?php

namespace App\Controller;

use App\Entity\Sell;
use App\Entity\SellImage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Doctrine\Persistence\ManagerRegistry;

class SellImageFileController
{
    public function __construct(private ManagerRegistry $doctrine, Security $security) {
        $this->security = $security;
    }

    public function __invoke(Request $request) {
        $em = $this->doctrine->getManager();
        $file = $request->files->get('file');

        if (!$file) {
            throw new BadRequestHttpException('Missing file');
        }
        
        $sellId = str_replace('/api/sales/', '', $request->get('sell'));
        $sell = $em->getRepository(Sell::class)->find($sellId);

        if (!$sell) {
            throw new AccessDeniedException("Accès refusé");
        }

        if (count($sell->getImages()) >= 5) {
            return new JsonResponse("Pas plus de 5 photos par vente", JsonResponse::HTTP_BAD_REQUEST);
        }

        $sellImage = new SellImage();
        $sellImage->setFile($file);
        $sellImage->setCreatedDate(new \DateTime());

        if (
            $this->security->isGranted('ROLE_SALES') &&
            $sell->getUser() != $this->security->getUser()
        ) {
            throw new AccessDeniedException("Accès refusé");
        }

        $sellImage->setSell($sell);

        return $sellImage;
    }
}