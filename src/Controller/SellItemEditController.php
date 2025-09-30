<?php

namespace App\Controller;

use App\Entity\Sell;
use App\Entity\SellItem;
use App\Entity\Service;
use App\Entity\ServicePackage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/api/sell/{sellId}/items")
 */
class SellItemEditController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * @Route("", name="sell_items_list", methods={"GET"})
     */
    public function listItems(int $sellId): JsonResponse
    {
        $sell = $this->getSellWithPermission($sellId);
        if (!$sell) {
            return new JsonResponse(['error' => 'Vente non trouvée ou accès refusé'], 404);
        }

        $items = [];
        foreach ($sell->getSellItems() as $item) {
            $items[] = [
                'id' => $item->getId(),
                'service' => $item->getService() ? [
                    'id' => $item->getService()->getId(),
                    'name' => $item->getService()->getName(),
                    'price' => $item->getService()->getPrice(),
                    'tva' => $item->getService()->getTva()
                ] : null,
                'servicePackage' => $item->getServicePackage() ? [
                    'id' => $item->getServicePackage()->getId(),
                    'name' => $item->getServicePackage()->getName(),
                    'price' => $item->getServicePackage()->getPrice()
                ] : null,
                'quantity' => $item->getQuantity(),
                'customPrice' => $item->getCustomPrice(),
                'isOffered' => $item->getIsOffered(),
                'comment' => $item->getComment()
            ];
        }

        return new JsonResponse([
            'sellId' => $sellId,
            'canEdit' => $this->canEditSellItems($sell),
            'items' => $items
        ]);
    }

    /**
     * @Route("/{itemId}", name="sell_item_update", methods={"PUT", "PATCH"})
     */
    public function updateItem(Request $request, int $sellId, int $itemId): JsonResponse
    {
        $sell = $this->getSellWithPermission($sellId);
        if (!$sell) {
            return new JsonResponse(['error' => 'Vente non trouvée ou accès refusé'], 404);
        }

        if (!$this->canEditSellItems($sell)) {
            return new JsonResponse(['error' => 'Modification non autorisée pour cette vente'], 403);
        }

        $sellItem = $this->entityManager->getRepository(SellItem::class)->findOneBy([
            'id' => $itemId,
            'sell' => $sell
        ]);

        if (!$sellItem) {
            return new JsonResponse(['error' => 'Item non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        try {
            // Mise à jour des champs modifiables
            if (isset($data['quantity'])) {
                $sellItem->setQuantity(max(1, intval($data['quantity'])));
            }

            if (isset($data['customPrice'])) {
                $sellItem->setCustomPrice($data['customPrice'] ? floatval($data['customPrice']) : null);
            }

            if (isset($data['isOffered'])) {
                $sellItem->setIsOffered(boolval($data['isOffered']));
            }

            if (isset($data['comment'])) {
                $sellItem->setComment($data['comment']);
            }

            // Changement de service/package (plus complexe)
            if (isset($data['serviceId']) || isset($data['servicePackageId'])) {
                if (isset($data['serviceId']) && $data['serviceId']) {
                    $service = $this->entityManager->getRepository(Service::class)->find($data['serviceId']);
                    if ($service) {
                        $sellItem->setService($service);
                        $sellItem->setServicePackage(null);
                    }
                } elseif (isset($data['servicePackageId']) && $data['servicePackageId']) {
                    $servicePackage = $this->entityManager->getRepository(ServicePackage::class)->find($data['servicePackageId']);
                    if ($servicePackage) {
                        $sellItem->setServicePackage($servicePackage);
                        $sellItem->setService(null);
                    }
                }
            }

            $this->entityManager->flush();

            // Recalculer les totaux de la vente
            $this->recalculateSellTotals($sell);

            return new JsonResponse([
                'success' => true,
                'message' => 'Item mis à jour avec succès',
                'item' => [
                    'id' => $sellItem->getId(),
                    'quantity' => $sellItem->getQuantity(),
                    'customPrice' => $sellItem->getCustomPrice(),
                    'isOffered' => $sellItem->getIsOffered(),
                    'comment' => $sellItem->getComment()
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la mise à jour',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @Route("", name="sell_item_add", methods={"POST"})
     */
    public function addItem(Request $request, int $sellId): JsonResponse
    {
        $sell = $this->getSellWithPermission($sellId);
        if (!$sell) {
            return new JsonResponse(['error' => 'Vente non trouvée ou accès refusé'], 404);
        }

        if (!$this->canEditSellItems($sell)) {
            return new JsonResponse(['error' => 'Modification non autorisée pour cette vente'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['serviceId']) && !isset($data['servicePackageId'])) {
            return new JsonResponse(['error' => 'Service ou package requis'], 400);
        }

        try {
            $sellItem = new SellItem();
            $sellItem->setSell($sell);
            $sellItem->setQuantity($data['quantity'] ?? 1);
            $sellItem->setCustomPrice($data['customPrice'] ?? null);
            $sellItem->setIsOffered($data['isOffered'] ?? false);
            $sellItem->setComment($data['comment'] ?? null);

            if (isset($data['serviceId'])) {
                $service = $this->entityManager->getRepository(Service::class)->find($data['serviceId']);
                if (!$service) {
                    return new JsonResponse(['error' => 'Service non trouvé'], 404);
                }
                $sellItem->setService($service);
            } elseif (isset($data['servicePackageId'])) {
                $servicePackage = $this->entityManager->getRepository(ServicePackage::class)->find($data['servicePackageId']);
                if (!$servicePackage) {
                    return new JsonResponse(['error' => 'Package non trouvé'], 404);
                }
                $sellItem->setServicePackage($servicePackage);
            }

            $this->entityManager->persist($sellItem);
            $this->entityManager->flush();

            // Recalculer les totaux de la vente
            $this->recalculateSellTotals($sell);

            return new JsonResponse([
                'success' => true,
                'message' => 'Item ajouté avec succès',
                'itemId' => $sellItem->getId()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'ajout',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @Route("/{itemId}", name="sell_item_delete", methods={"DELETE"})
     */
    public function deleteItem(int $sellId, int $itemId): JsonResponse
    {
        $sell = $this->getSellWithPermission($sellId);
        if (!$sell) {
            return new JsonResponse(['error' => 'Vente non trouvée ou accès refusé'], 404);
        }

        if (!$this->canEditSellItems($sell)) {
            return new JsonResponse(['error' => 'Modification non autorisée pour cette vente'], 403);
        }

        $sellItem = $this->entityManager->getRepository(SellItem::class)->findOneBy([
            'id' => $itemId,
            'sell' => $sell
        ]);

        if (!$sellItem) {
            return new JsonResponse(['error' => 'Item non trouvé'], 404);
        }

        try {
            $this->entityManager->remove($sellItem);
            $this->entityManager->flush();

            // Recalculer les totaux de la vente
            $this->recalculateSellTotals($sell);

            return new JsonResponse([
                'success' => true,
                'message' => 'Item supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la suppression',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function getSellWithPermission(int $sellId): ?Sell
    {
        $sell = $this->entityManager->getRepository(Sell::class)->find($sellId);

        if (!$sell) {
            return null;
        }

        $user = $this->security->getUser();

        // Vérifier les permissions
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            if ($sell->getUser() !== $user) {
                // Vérifier si c'est un commercial additionnel
                $additionalSellers = $sell->getAdditionalSellers() ?? [];
                $isAdditionalSeller = false;

                foreach ($additionalSellers as $additionalSeller) {
                    if (isset($additionalSeller['id']) && $additionalSeller['id'] == $user->getId()) {
                        $isAdditionalSeller = true;
                        break;
                    }
                }

                if (!$isAdditionalSeller && $sell->getAdditionnalSeller() !== $user) {
                    return null;
                }
            }
        }

        return $sell;
    }

    private function canEditSellItems(Sell $sell): bool
    {
        $editableStatuses = [
            'En attente FDR',
            'Dossier incomplet',
            'VENTE A REVOIR'
        ];

        return in_array($sell->getStatus(), $editableStatuses);
    }

    private function recalculateSellTotals(Sell $sell): void
    {
        // Recalcul des totaux basé sur les SellItems actuels
        $totalHT = 0;
        $totalTVA = 0;

        foreach ($sell->getSellItems() as $item) {
            if ($item->getIsOffered()) {
                continue; // Les prestations offertes ne comptent pas dans le total
            }

            $itemPrice = 0;
            $itemTVA = 0;

            if ($item->getCustomPrice()) {
                $itemPrice = $item->getCustomPrice() * $item->getQuantity();
            } elseif ($item->getService()) {
                $itemPrice = $item->getService()->getPrice() * $item->getQuantity();
                $itemTVA = ($itemPrice * $item->getService()->getTva()) / 100;
            } elseif ($item->getServicePackage()) {
                $itemPrice = $item->getServicePackage()->getPrice() * $item->getQuantity();
                // TVA forfait à définir ou calculer selon les services inclus
            }

            $totalHT += $itemPrice;
            $totalTVA += $itemTVA;
        }

        // Mise à jour du financialSection
        $financialSection = $sell->getFinancialSection() ?? [];
        $financialSection['price'] = $totalHT;
        $financialSection['totalTax'] = $totalTVA;
        $financialSection['totalWithTax'] = $totalHT + $totalTVA;

        $sell->setFinancialSection($financialSection);
        $this->entityManager->flush();
    }
}