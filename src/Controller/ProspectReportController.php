<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/api/prospects")
 */
class ProspectReportController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * @Route("/report", name="prospects_report", methods={"GET"})
     */
    public function generateReport(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user || !$user->getEnabled()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $userId = $request->query->get('user_id');

        // Vérifier les permissions
        if ($userId && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_PROFIL_B')) {
            if ($userId != $user->getId()) {
                return new JsonResponse(['error' => 'Accès refusé aux données d\'un autre utilisateur'], 403);
            }
        }

        $appointments = $this->getAppointmentsForReport($userId, $startDate, $endDate, $user);
        $reportData = $this->generateReportData($appointments, $startDate, $endDate);

        return new JsonResponse($reportData);
    }

    /**
     * @Route("/report/download", name="prospects_report_download", methods={"GET"})
     */
    public function downloadReport(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user || !$user->getEnabled()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $userId = $request->query->get('user_id');
        $format = $request->query->get('format', 'pdf'); // pdf, csv, excel

        // Vérifier les permissions
        if ($userId && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN') && !$this->isGranted('ROLE_PROFIL_B')) {
            if ($userId != $user->getId()) {
                return new JsonResponse(['error' => 'Accès refusé aux données d\'un autre utilisateur'], 403);
            }
        }

        $appointments = $this->getAppointmentsForReport($userId, $startDate, $endDate, $user);

        switch ($format) {
            case 'csv':
                return $this->generateCsvReport($appointments, $startDate, $endDate);
            case 'excel':
                return $this->generateExcelReport($appointments, $startDate, $endDate);
            case 'pdf':
            default:
                return $this->generatePdfReport($appointments, $startDate, $endDate);
        }
    }

    private function getAppointmentsForReport(?string $userId, ?string $startDate, ?string $endDate, User $currentUser): array
    {
        $qb = $this->entityManager->getRepository(Appointment::class)->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->leftJoin('a.customer', 'c')
            ->addSelect('u', 'c');

        // Filtrage par utilisateur
        if ($userId) {
            $qb->andWhere('u.id = :userId')->setParameter('userId', $userId);
        } elseif (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            // Pour PROFIL_B, filtrer par utilisateurs gérés
            if ($this->isGranted('ROLE_PROFIL_B')) {
                $managedUserIds = array_map(fn($u) => $u->getId(), $currentUser->getManagedUsers()->toArray());
                $managedUserIds[] = $currentUser->getId(); // Inclure soi-même

                if (!empty($managedUserIds)) {
                    $qb->andWhere('u.id IN (:managedUsers)')->setParameter('managedUsers', $managedUserIds);
                } else {
                    $qb->andWhere('u.id = :currentUser')->setParameter('currentUser', $currentUser->getId());
                }
            } else {
                // Commercial normal : seulement ses prospects
                $qb->andWhere('u.id = :currentUser')->setParameter('currentUser', $currentUser->getId());
            }
        }

        // Filtrage par dates
        if ($startDate) {
            $qb->andWhere('a.createdDate >= :startDate')->setParameter('startDate', new \DateTime($startDate));
        }
        if ($endDate) {
            $qb->andWhere('a.createdDate <= :endDate')->setParameter('endDate', new \DateTime($endDate . ' 23:59:59'));
        }

        $qb->orderBy('a.createdDate', 'ASC');

        return $qb->getQuery()->getResult();
    }

    private function generateReportData(array $appointments, ?string $startDate, ?string $endDate): array
    {
        $stats = [
            'total' => count($appointments),
            'byStatus' => [],
            'byUser' => [],
            'byDate' => []
        ];

        $details = [];

        foreach ($appointments as $appointment) {
            $status = $appointment->getStatus();
            $userName = $appointment->getUser() ?
                $appointment->getUser()->getFirstname() . ' ' . $appointment->getUser()->getLastname() :
                'Non assigné';
            $date = $appointment->getCreatedDate()->format('Y-m-d');

            // Statistiques par statut
            if (!isset($stats['byStatus'][$status])) {
                $stats['byStatus'][$status] = 0;
            }
            $stats['byStatus'][$status]++;

            // Statistiques par utilisateur
            if (!isset($stats['byUser'][$userName])) {
                $stats['byUser'][$userName] = 0;
            }
            $stats['byUser'][$userName]++;

            // Statistiques par date
            if (!isset($stats['byDate'][$date])) {
                $stats['byDate'][$date] = 0;
            }
            $stats['byDate'][$date]++;

            // Détails du prospect
            $details[] = [
                'id' => $appointment->getId(),
                'customer' => $appointment->getCustomer() ? [
                    'firstname' => $appointment->getCustomer()->getFirstname(),
                    'lastname' => $appointment->getCustomer()->getLastname(),
                    'phone' => $appointment->getCustomer()->getPhone(),
                    'mobile' => $appointment->getCustomer()->getMobile(),
                    'email' => $appointment->getCustomer()->getEmail(),
                    'address' => $appointment->getCustomer()->getAddress(),
                    'postcode' => $appointment->getCustomer()->getPostcode(),
                    'city' => $appointment->getCustomer()->getCity()
                ] : null,
                'user' => $userName,
                'status' => $status,
                'teleoperatorName' => $appointment->getTeleoperatorName(),
                'appointmentDate' => $appointment->getAppointmentDate() ? $appointment->getAppointmentDate()->format('Y-m-d H:i') : null,
                'replacementDate' => $appointment->getReplacementDate() ? $appointment->getReplacementDate()->format('Y-m-d H:i') : null,
                'createdDate' => $appointment->getCreatedDate()->format('Y-m-d H:i'),
                'comment' => $appointment->getComment()
            ];
        }

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'statistics' => $stats,
            'details' => $details,
            'generatedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            'generatedBy' => $this->security->getUser() ?
                $this->security->getUser()->getFirstname() . ' ' . $this->security->getUser()->getLastname() :
                'Système'
        ];
    }

    private function generatePdfReport(array $appointments, ?string $startDate, ?string $endDate): Response
    {
        $reportData = $this->generateReportData($appointments, $startDate, $endDate);

        // Configuration PDF basique (vous pouvez utiliser une lib comme TCPDF ou DomPDF)
        $html = $this->renderView('reports/prospects_report.html.twig', [
            'data' => $reportData
        ]);

        // Retourner le contenu HTML pour l'instant (à remplacer par génération PDF réelle)
        return new Response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="rapport_prospects_' . date('Y-m-d') . '.html"'
        ]);
    }

    private function generateCsvReport(array $appointments, ?string $startDate, ?string $endDate): Response
    {
        $reportData = $this->generateReportData($appointments, $startDate, $endDate);

        $csvContent = "ID,Client,Commercial,Téléopérateur,Statut,Date RDV,Date création,Commentaire\n";

        foreach ($reportData['details'] as $detail) {
            $customer = $detail['customer'];
            $csvContent .= sprintf(
                "%d,\"%s %s\",%s,%s,%s,%s,%s,\"%s\"\n",
                $detail['id'],
                $customer ? $customer['firstname'] : '',
                $customer ? $customer['lastname'] : '',
                $detail['user'],
                $detail['teleoperatorName'] ?? '',
                $detail['status'],
                $detail['appointmentDate'] ?? '',
                $detail['createdDate'],
                str_replace('"', '""', $detail['comment'] ?? '')
            );
        }

        return new Response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="rapport_prospects_' . date('Y-m-d') . '.csv"'
        ]);
    }

    private function generateExcelReport(array $appointments, ?string $startDate, ?string $endDate): Response
    {
        // Implémentation Excel avec PhpSpreadsheet ou retourner CSV pour l'instant
        return $this->generateCsvReport($appointments, $startDate, $endDate);
    }
}