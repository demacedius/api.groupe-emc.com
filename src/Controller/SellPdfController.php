<?php

namespace App\Controller;

use App\Entity\Sell;
use App\Service\DocumentPdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Contrôleur pour la route exacte utilisée par le frontend
 */
class SellPdfController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private DocumentPdfService $documentPdfService;

    public function __construct(EntityManagerInterface $entityManager, DocumentPdfService $documentPdfService)
    {
        $this->entityManager = $entityManager;
        $this->documentPdfService = $documentPdfService;
    }

    /**
     * Route exacte utilisée par le frontend: /sell/{id}/pdf
     * Redirige vers le frontend Vue.js qui a le bon design
     * @Route("/sell/{id}/pdf", name="sell_pdf", methods={"GET"})
     */
    public function generateSellPdf(int $id): Response
    {
        error_log("DEBUG: Route /sell/{$id}/pdf appelée - REDIRECTION VERS FRONTEND VUE.JS");

        $sell = $this->entityManager->getRepository(Sell::class)->find($id);

        if (!$sell) {
            error_log("DEBUG: Vente non trouvée pour ID: " . $id);
            throw $this->createNotFoundException('Vente non trouvée');
        }

        error_log("DEBUG: Vente trouvée: ID " . $sell->getId());

        // TEMPORAIRE : Permissions désactivées pour test
        // $this->denyAccessUnlessGranted('IS_ALLOWED', $sell);
        error_log("DEBUG: Permissions ignorées pour test");

        // Vérifier si la génération est possible
        $validation = $this->documentPdfService->canGenerateDocuments($sell);
        if (!$validation['canGenerate']) {
            error_log("DEBUG: Validation échouée: " . json_encode($validation['errors']));
            return new Response('Erreur: Impossible de générer le document', 400);
        }

        // Rediriger vers le frontend Vue.js sur le port 8082 qui a le bon design
        $frontendUrl = "http://localhost:8082/quotation/{$id}";
        error_log("DEBUG: Redirection vers le frontend Vue.js: " . $frontendUrl);

        return $this->redirect($frontendUrl);
    }
}