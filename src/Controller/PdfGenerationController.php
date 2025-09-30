<?php

namespace App\Controller;

use App\Entity\Sell;
use App\Service\DocumentPdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @Route("/api/pdf")
 */
class PdfGenerationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private DocumentPdfService $documentPdfService;

    public function __construct(EntityManagerInterface $entityManager, DocumentPdfService $documentPdfService)
    {
        $this->entityManager = $entityManager;
        $this->documentPdfService = $documentPdfService;
    }

    /**
     * @Route("/sell/{id}/config", name="pdf_sell_config", methods={"GET"})
     */
    public function getSellPdfConfiguration(int $id): JsonResponse
    {
        $sell = $this->entityManager->getRepository(Sell::class)->find($id);

        if (!$sell) {
            throw $this->createNotFoundException('Vente non trouvée');
        }

        // Calculer la meilleure répartition des pages
        $sellItems = $sell->getSellItems();
        $itemCount = count($sellItems);
        
        $pdfConfig = [
            'itemCount' => $itemCount,
            'recommendedPagination' => $this->calculateOptimalPagination($itemCount),
            'hasPackages' => $this->hasServicePackages($sellItems),
            'estimatedPages' => $this->estimatePageCount($itemCount),
            'quality' => $this->getRecommendedQuality($itemCount)
        ];

        return new JsonResponse($pdfConfig);
    }

    /**
     * @Route("/sell/{id}/optimize", name="pdf_sell_optimize", methods={"POST"})
     */
    public function optimizeSellForPdf(int $id): JsonResponse
    {
        $sell = $this->entityManager->getRepository(Sell::class)->find($id);

        if (!$sell) {
            throw $this->createNotFoundException('Vente non trouvée');
        }

        // Optimisations pour la génération PDF
        $optimizations = [
            'pageBreaks' => $this->calculateOptimalPageBreaks($sell),
            'imageOptimization' => $this->getImageOptimizations($sell),
            'layoutAdjustments' => $this->getLayoutAdjustments($sell)
        ];

        return new JsonResponse($optimizations);
    }

    private function calculateOptimalPagination(int $itemCount): array
    {
        if ($itemCount <= 6) {
            return ['firstPage' => $itemCount, 'otherPages' => 0, 'totalPages' => 1];
        }

        $firstPageItems = 6;
        $remainingItems = $itemCount - $firstPageItems;
        
        // 8 items par page intermédiaire, 5 minimum sur la dernière page
        $otherPagesCount = ceil($remainingItems / 8);
        if ($remainingItems % 8 > 0 && $remainingItems % 8 < 5) {
            // Redistribuer pour éviter une dernière page avec trop peu d'items
            $otherPagesCount = ceil($remainingItems / 7);
        }

        return [
            'firstPage' => $firstPageItems,
            'otherPages' => 8,
            'lastPage' => max(5, $remainingItems % 8),
            'totalPages' => 1 + $otherPagesCount
        ];
    }

    private function hasServicePackages($sellItems): bool
    {
        foreach ($sellItems as $item) {
            if ($item->getServicePackage() !== null) {
                return true;
            }
        }
        return false;
    }

    private function estimatePageCount(int $itemCount): int
    {
        if ($itemCount <= 6) return 1;
        return 1 + ceil(($itemCount - 6) / 8);
    }

    private function getRecommendedQuality(int $itemCount): array
    {
        // Adapter la qualité selon la complexité
        if ($itemCount > 20) {
            return ['dpi' => 150, 'scale' => 3, 'quality' => 0.9];
        } elseif ($itemCount > 10) {
            return ['dpi' => 175, 'scale' => 3.5, 'quality' => 0.95];
        } else {
            return ['dpi' => 192, 'scale' => 4, 'quality' => 1.0];
        }
    }

    private function calculateOptimalPageBreaks(Sell $sell): array
    {
        $sellItems = $sell->getSellItems();
        $pageBreaks = [];
        
        // Éviter de couper les forfaits sur plusieurs pages
        $currentPage = 1;
        $itemsOnCurrentPage = 0;
        $maxItemsFirstPage = 6;
        $maxItemsOtherPages = 8;
        
        foreach ($sellItems as $index => $item) {
            $maxItems = ($currentPage === 1) ? $maxItemsFirstPage : $maxItemsOtherPages;
            
            // Si c'est un forfait et qu'il risque d'être coupé, forcer un saut de page
            if ($item->getServicePackage() && $itemsOnCurrentPage >= ($maxItems - 1)) {
                $pageBreaks[] = $index;
                $currentPage++;
                $itemsOnCurrentPage = 0;
            }
            
            $itemsOnCurrentPage++;
            
            if ($itemsOnCurrentPage >= $maxItems) {
                if ($index < count($sellItems) - 1) { // Pas sur le dernier item
                    $pageBreaks[] = $index + 1;
                    $currentPage++;
                    $itemsOnCurrentPage = 0;
                }
            }
        }
        
        return $pageBreaks;
    }

    private function getImageOptimizations(Sell $sell): array
    {
        $images = $sell->getImages();
        return [
            'imageCount' => count($images),
            'recommendedCompression' => count($images) > 10 ? 0.8 : 0.9,
            'maxDimensions' => ['width' => 800, 'height' => 600]
        ];
    }

    private function getLayoutAdjustments(Sell $sell): array
    {
        $hasLongDescriptions = false;
        foreach ($sell->getSellItems() as $item) {
            if (strlen($item->getDescription() ?? '') > 200) {
                $hasLongDescriptions = true;
                break;
            }
        }

        return [
            'compactMode' => count($sell->getSellItems()) > 15,
            'reduceDescriptionSpace' => $hasLongDescriptions,
            'adjustMargins' => count($sell->getSellItems()) > 20
        ];
    }

    /**
     * @Route("/sell/{id}/bon-commande", name="pdf_sell_bon_commande", methods={"GET"})
     */
    public function generateBonCommande(int $id): Response
    {
        $sell = $this->entityManager->getRepository(Sell::class)->find($id);

        if (!$sell) {
            throw $this->createNotFoundException('Vente non trouvée');
        }

        // Vérifier les permissions (utiliser les mêmes que pour l'accès à la vente)
        $this->denyAccessUnlessGranted('IS_ALLOWED', $sell);

        // Vérifier si la génération est possible
        $validation = $this->documentPdfService->canGenerateDocuments($sell);
        if (!$validation['canGenerate']) {
            return new JsonResponse([
                'error' => 'Impossible de générer le document',
                'details' => $validation['errors']
            ], 400);
        }

        return $this->documentPdfService->generateBonCommande($sell);
    }

    /**
     * @Route("/sell/{id}/devis", name="pdf_sell_devis", methods={"GET"})
     */
    public function generateDevis(int $id): Response
    {
        error_log("DEBUG: PdfGenerationController::generateDevis appelé avec ID: " . $id);

        $sell = $this->entityManager->getRepository(Sell::class)->find($id);

        if (!$sell) {
            error_log("DEBUG: Vente non trouvée pour ID: " . $id);
            throw $this->createNotFoundException('Vente non trouvée');
        }

        error_log("DEBUG: Vente trouvée: " . $sell->getReference());

        // Vérifier les permissions
        $this->denyAccessUnlessGranted('IS_ALLOWED', $sell);
        error_log("DEBUG: Permissions OK");

        // Vérifier si la génération est possible
        $validation = $this->documentPdfService->canGenerateDocuments($sell);
        if (!$validation['canGenerate']) {
            error_log("DEBUG: Validation échoué: " . json_encode($validation['errors']));
            return new JsonResponse([
                'error' => 'Impossible de générer le document',
                'details' => $validation['errors']
            ], 400);
        }

        error_log("DEBUG: Validation OK, génération du PDF...");
        return $this->documentPdfService->generateDevis($sell);
    }

    /**
     * @Route("/sell/{id}/devis-html", name="pdf_sell_devis_html", methods={"GET"})
     */
    public function generateDevisHtml(int $id): Response
    {
        $sell = $this->entityManager->getRepository(Sell::class)->find($id);

        if (!$sell) {
            throw $this->createNotFoundException('Vente non trouvée');
        }

        // Vérifier les permissions
        $this->denyAccessUnlessGranted('IS_ALLOWED', $sell);

        // Retourner le HTML directement pour que le frontend puisse l'utiliser
        $html = $this->renderView('pdf/devis.html.twig', [
            'sell' => $sell
        ]);

        return new Response($html, 200, [
            'Content-Type' => 'text/html',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
        ]);
    }

    /**
     * @Route("/sell/{id}/documents-complets", name="pdf_sell_documents_complets", methods={"GET"})
     */
    public function generateDocumentsComplets(int $id): Response
    {
        $sell = $this->entityManager->getRepository(Sell::class)->find($id);

        if (!$sell) {
            throw $this->createNotFoundException('Vente non trouvée');
        }

        // Vérifier les permissions
        $this->denyAccessUnlessGranted('IS_ALLOWED', $sell);

        // Vérifier si la génération est possible
        $validation = $this->documentPdfService->canGenerateDocuments($sell);
        if (!$validation['canGenerate']) {
            return new JsonResponse([
                'error' => 'Impossible de générer le document',
                'details' => $validation['errors']
            ], 400);
        }

        return $this->documentPdfService->generateDocumentsComplets($sell);
    }

    /**
     * @Route("/sell/{id}/validation", name="pdf_sell_validation", methods={"GET"})
     */
    public function validateSellForPdf(int $id): JsonResponse
    {
        $sell = $this->entityManager->getRepository(Sell::class)->find($id);

        if (!$sell) {
            throw $this->createNotFoundException('Vente non trouvée');
        }

        // Vérifier les permissions
        $this->denyAccessUnlessGranted('IS_ALLOWED', $sell);

        $validation = $this->documentPdfService->canGenerateDocuments($sell);

        return new JsonResponse($validation);
    }
}