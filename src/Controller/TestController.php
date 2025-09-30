<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    /**
     * @Route("/test-public", name="test_public", methods={"GET"})
     */
    public function testPublic(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'API publique fonctionne !',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * @Route("/api/test", name="api_test", methods={"GET"})
     */
    public function test(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'API protégée fonctionne !',
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $this->getUser() ? $this->getUser()->getUsername() : 'Non connecté'
        ]);
    }
}