<?php

namespace App\Repository;

use App\Entity\Sell;
use App\Entity\Company; // Add this line
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sell>
 *
 * @method Sell|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sell|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sell[]    findAll()
 * @method Sell[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SellRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sell::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Sell $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Sell $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findSalesByUserAndStatus($id, $allowedStatus)
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.additionnalSeller', 'ad')
            ->where('u.id = :id OR ad.id = :id')
            ->setParameter('id', $id)
            ->andWhere('s.status IN (:allowed)')
            ->setParameter('allowed', $allowedStatus)
            ->orderBy('s.id', 'DESC');

        return $qb->getQuery()
                    ->getResult();
    }

    public function findSalesByUsersExcludingStatus(array $userIds, array $excludedStatus)
    {
        if (empty($userIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('s');
        $qb->select('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.additionnalSeller', 'ad')
            ->where('u.id IN (:userIds) OR ad.id IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->andWhere('s.status NOT IN (:excluded)')
            ->setParameter('excluded', $excludedStatus)
            ->orderBy('s.id', 'DESC');

        return $qb->getQuery()
                    ->getResult();
    } 
    
    /**
     * @param int $month
     * @param int $year
     * 
     * @return Sell[]
     */
    public function findCurrentMonthSales($month = null, $year = null)
    {
        if ($month === null) {
            $month = (int) date('m');
        }
    
        if ($year === null) {
            $year = (int) date('Y');
        }
    
        $startDate = new \DateTimeImmutable("$year-$month-01T00:00:00");
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('s');

        $qb->select('s')
            ->where('s.created_date BETWEEN :start AND :end')
            ->setParameter('start', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('end', $endDate->format('Y-m-d H:i:s'));

        return $qb->getQuery()
                    ->getResult();
    }

    /**
     * Get the last month/year that has sales data
     * @return array ['month' => int, 'year' => int]
     */
    public function getLastMonthWithSales(): array
    {
        $qb = $this->createQueryBuilder('s');
        $result = $qb->select('s')
            ->orderBy('s.created_date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        
        if ($result && $result->getCreatedDate()) {
            $date = $result->getCreatedDate();
            if ($date instanceof \DateTime) {
                return [
                    'month' => (int)$date->format('n'),
                    'year' => (int)$date->format('Y')
                ];
            } else {
                $dateTime = new \DateTime($date);
                return [
                    'month' => (int)$dateTime->format('n'),
                    'year' => (int)$dateTime->format('Y')
                ];
            }
        }
        
        // Fallback to current month if no sales
        return [
            'month' => (int)date('n'),
            'year' => (int)date('Y')
        ];
    }

    /**
     * @param int $month
     * @param int $year
     * 
     * @return Sell[]
     */
    public function findCurrentMonthSalesByUser($userId, $month = null, $year = null)
    {
        // If no month/year provided, use the last month with sales
        if ($month === null || $year === null) {
            $lastMonth = $this->getLastMonthWithSales();
            $month = $lastMonth['month'];
            $year = $lastMonth['year'];
        }
        
        $startDate = new \DateTimeImmutable("$year-$month-01T00:00:00");
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);
        
        $qb = $this->createQueryBuilder('s');

        $qb->select('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.additionnalSeller', 'ad')
            ->where('u.id = :id OR ad.id = :id')
            ->andWhere('s.created_date BETWEEN :start AND :end')
            ->setParameter('id', $userId)
            ->setParameter('start', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('end', $endDate->format('Y-m-d H:i:s'));

        return $qb->getQuery()
                    ->getResult();
    }

    /**
     * @param Company $company
     * @param int $month
     * @param int $year
     *
     * @return Sell[]
     */
    public function findSalesByCompanyAndMonth(Company $company, int $month, int $year): array
    {
        $startDate = new \DateTimeImmutable("$year-$month-01T00:00:00");
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('s')
            ->andWhere('s.company = :company')
            ->andWhere('s.created_date BETWEEN :start AND :end')
            ->setParameter('company', $company)
            ->setParameter('start', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('end', $endDate->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();
    }
}
