<?php

namespace App\Service;

use App\Entity\Sell;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;

class DocumentPdfService
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Génère le bon de commande PDF (4 pages)
     */
    public function generateBonCommande(Sell $sell): Response
    {
        $html = $this->twig->render('pdf/bon_commande.html.twig', [
            'sell' => $sell
        ]);

        return $this->generatePdf($html, "Bon_Commande_{$sell->getId()}.pdf");
    }

    /**
     * Génère le devis PDF (4 pages)
     */
    public function generateDevis(Sell $sell): Response
    {
        // DEBUG: Ajouter un log pour vérifier que cette méthode est appelée
        error_log("DEBUG: generateDevis appelé pour sell ID " . $sell->getId());

        $html = $this->twig->render('pdf/devis_vue.html.twig', [
            'sell' => $sell
        ]);

        error_log("DEBUG: Template rendu, taille HTML: " . strlen($html) . " bytes");

        return $this->generatePdf($html, "Devis_{$sell->getId()}.pdf");
    }

    /**
     * Génère les documents complets (8 pages : 4 bon de commande + 4 devis)
     */
    public function generateDocumentsComplets(Sell $sell): Response
    {
        // Générer le bon de commande
        $bonCommandeHtml = $this->twig->render('pdf/bon_commande.html.twig', [
            'sell' => $sell
        ]);

        // Générer le devis
        $devisHtml = $this->twig->render('pdf/devis.html.twig', [
            'sell' => $sell
        ]);

        // Combiner les deux documents
        $combinedHtml = $bonCommandeHtml . $devisHtml;

        return $this->generatePdf($combinedHtml, "Documents_Complets_{$sell->getId()}.pdf");
    }

    /**
     * Méthode privée pour générer le PDF avec DomPDF
     */
    private function generatePdf(string $html, string $filename): Response
    {
        // Configuration DomPDF pour une meilleure qualité
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultPaperSize', 'A4');
        $options->set('defaultPaperOrientation', 'portrait');

        // Initialiser DomPDF
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        // Rendu du PDF
        $dompdf->render();

        // Créer la response
        $response = new Response($dompdf->output());

        // Headers pour ouverture dans le navigateur (pas de téléchargement forcé)
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', sprintf('inline; filename="%s"', $filename));
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'public');

        return $response;
    }

    /**
     * Valide si une vente peut générer des documents PDF
     */
    public function canGenerateDocuments(Sell $sell): array
    {
        $errors = [];

        // Vérifications obligatoires
        if (!$sell->getCustomer()) {
            $errors[] = 'Aucun client associé à la vente';
        }

        if (!$sell->getUser()) {
            $errors[] = 'Aucun commercial associé à la vente';
        }

        if ($sell->getSellItems()->isEmpty()) {
            $errors[] = 'Aucune prestation dans la vente';
        }

        if (!$sell->getTotal() || $sell->getTotal() <= 0) {
            $errors[] = 'Montant total invalide';
        }

        if (!$sell->getId()) {
            $errors[] = 'ID de vente manquant';
        }

        // Vérifications client
        if ($sell->getCustomer()) {
            $customer = $sell->getCustomer();
            if (!$customer->getFirstname() || !$customer->getLastname()) {
                $errors[] = 'Nom et prénom du client requis';
            }
            if (!$customer->getAddress() || !$customer->getPostcode() || !$customer->getCity()) {
                $errors[] = 'Adresse complète du client requise';
            }
            if (!$customer->getPhone() && !$customer->getMobile()) {
                $errors[] = 'Téléphone du client requis';
            }
        }

        // Vérifications commercial
        if ($sell->getUser()) {
            $user = $sell->getUser();
            if (!$user->getFirstname() || !$user->getLastname()) {
                $errors[] = 'Nom et prénom du commercial requis';
            }
        }

        return [
            'canGenerate' => empty($errors),
            'errors' => $errors
        ];
    }
}