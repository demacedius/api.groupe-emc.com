<?php

namespace App\Controller;

use App\Entity\Sell;
use App\Service\SellEditValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api/sell/edit-in-progress", name="sell_edit_in_progress_")
 */
class EditSellInProgressController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SellEditValidationService $validationService,
        private SerializerInterface $serializer
    ) {
    }

    /**
     * @Route("/{id}/check", name="check", methods={"GET"})
     */
    public function checkEditability(int $id): JsonResponse
    {
        $sell = $this->entityManager->getRepository(Sell::class)->find($id);

        if (!$sell) {
            return new JsonResponse(['error' => 'Vente non trouvée'], 404);
        }

        $canEdit = $this->validationService->canEditSellInProgress($sell);
        $editableFields = $this->validationService->getEditableFields($sell);

        return new JsonResponse([
            'canEdit' => $canEdit,
            'editableFields' => $editableFields,
            'status' => $sell->getStatus(),
            'message' => $canEdit
                ? 'La vente peut être modifiée'
                : 'La vente ne peut pas être modifiée dans son état actuel'
        ]);
    }

    /**
     * @Route("/{id}/update", name="update", methods={"PATCH"})
     */
    public function updateSellInProgress(Request $request, int $id): JsonResponse
    {
        $sell = $this->entityManager->getRepository(Sell::class)->find($id);

        if (!$sell) {
            return new JsonResponse(['error' => 'Vente non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }

        // Vérifier les permissions
        if (!$this->validationService->canEditSellInProgress($sell)) {
            return new JsonResponse([
                'error' => 'Vous n\'avez pas les permissions pour modifier cette vente'
            ], 403);
        }

        // Valider la modification
        $errors = $this->validationService->validateEdit($sell, $data);
        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        try {
            // Appliquer les modifications
            $this->applyChangesToSell($sell, $data);

            // Logger la modification
            $this->logSellModification($sell, $data);

            $this->entityManager->persist($sell);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Vente modifiée avec succès',
                'sell' => $this->serializer->serialize($sell, 'json', ['groups' => ['sell:read']])
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la modification : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @Route("/{id}/history", name="history", methods={"GET"})
     */
    public function getModificationHistory(int $id): JsonResponse
    {
        $sell = $this->entityManager->getRepository(Sell::class)->find($id);

        if (!$sell) {
            return new JsonResponse(['error' => 'Vente non trouvée'], 404);
        }

        // Récupérer l'historique des modifications
        // Pour l'instant, retourner les informations basiques
        return new JsonResponse([
            'sellId' => $sell->getId(),
            'currentStatus' => $sell->getStatus(),
            'createdDate' => $sell->getCreatedDate(),
            'lastModified' => $sell->getUpdatedAt(),
            'modifications' => [
                // Ici on pourrait implémenter un système de logs plus sophistiqué
                [
                    'date' => $sell->getUpdatedAt(),
                    'user' => $sell->getUser() ? $sell->getUser()->getFirstname() . ' ' . $sell->getUser()->getLastname() : null,
                    'action' => 'Dernière modification',
                    'fields' => []
                ]
            ]
        ]);
    }

    private function applyChangesToSell(Sell $sell, array $data): void
    {
        $editableFields = $this->validationService->getEditableFields($sell);

        foreach ($data as $field => $value) {
            if (!in_array($field, $editableFields)) {
                continue; // Skip non-editable fields
            }

            switch ($field) {
                case 'discountAmount':
                    $sell->setDiscountAmount($value);
                    break;
                case 'discountType':
                    $sell->setDiscountType($value);
                    break;
                case 'paymentMethod':
                    $sell->setPaymentMethod($value);
                    break;
                case 'workDate':
                    $sell->setWorkDate($value ? new \DateTime($value) : null);
                    break;
                case 'followup':
                    $sell->setFollowup($value);
                    break;
                case 'fees':
                    $sell->setFees($value);
                    break;
                case 'depositAmount':
                    $sell->setDepositAmount($value);
                    break;
                case 'balanceAmount':
                    $sell->setBalanceAmount($value);
                    break;
                case 'additionalSellers':
                    $sell->setAdditionalSellers($value);
                    break;
                case 'customStatusComment':
                    $sell->setCustomStatusComment($value);
                    break;
                case 'fileFeesDisabled':
                    $sell->setFileFeesDisabled($value);
                    break;
                case 'hasOfferedServices':
                    // Ce champ sera géré dans le modèle frontend
                    break;
                case 'offeredServicesDescription':
                    // Ce champ sera géré dans le modèle frontend
                    break;
            }
        }

        $sell->setUpdatedAt(new \DateTime());
    }

    private function logSellModification(Sell $sell, array $data): void
    {
        // Ici on pourrait implémenter un système de logs plus sophistiqué
        // Pour l'instant, on met juste à jour la date de modification
        $sell->setUpdatedAt(new \DateTime());
    }
}