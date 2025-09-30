<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Customer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[]    findAll()
 * @method Customer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Customer $entity, bool $flush = true): void
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
    public function remove(Customer $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @return Customer[] Returns an array of Customer objects
     */
    public function findR1Customers()
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.sales', 's')
            ->andWhere('s.source = :source')
            ->setParameter('source', 'R1')
            ->andWhere('c.status <> :status')
            ->setParameter('status', 'Prospect')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param int $month
     * @param int $year
     * 
     * @return Customer[]
     */
    public function findCurrentMonthCustomers($month = null, $year = null)
    {
        if ($month === null) {
            $month = (int) date('m');
        }
    
        if ($year === null) {
            $year = (int) date('Y');
        }
    
        $startDate = new \DateTimeImmutable("$year-$month-01T00:00:00");
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('c');

        $qb->select('c')
            ->where('c.created_date BETWEEN :start AND :end')
            ->andWhere('c.status <> :prospect')
            ->setParameter('start', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('end', $endDate->format('Y-m-d H:i:s'))
            ->setParameter('prospect', "Prospect");

        return $qb->getQuery()
                    ->getResult();
    } 

    /**
     * @return Customer[] Returns an array of Customer objects
     */
    public function findAllProspectsByUser($userId)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.appointments', 'a')
            ->leftJoin('a.user', 'u')
            ->andWhere('a.closed = false')
            ->andWhere('u.id = :user')
            ->setParameter('user', $userId)
            ->addOrderBy('CASE WHEN a.status = :replacerStatus AND a.replacementDate IS NOT NULL AND a.replacementDate <= NOW() THEN a.replacementDate ELSE a.createdDate END', 'ASC')
            ->setParameter('replacerStatus', 'A REPLACER')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Customer[] Returns an array of Customer objects
     */
    public function findAllProspects()
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.appointments', 'a')
            ->andWhere('c.status = :status')
            ->andWhere('(a.closed = false OR a.closed IS NULL)')
            ->setParameter('status', "Prospect")
            ->orderBy('c.created_date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Customer[] Returns an array of Customer objects
     */
    public function findAllProspectsByUsers(array $userIds)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.appointments', 'a')
            ->leftJoin('a.user', 'u')
            ->andWhere('a.closed = false')
            ->andWhere('u.id IN (:users)')
            ->setParameter('users', $userIds)
            ->addOrderBy('CASE WHEN a.status = :replacerStatus AND a.replacementDate IS NOT NULL AND a.replacementDate <= NOW() THEN a.replacementDate ELSE a.createdDate END', 'ASC')
            ->setParameter('replacerStatus', 'A REPLACER')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Customer[] Returns an array of Customer objects
     */
    public function findAllNotProspects()
    {
        $today = new \Datetime();

        return $this->createQueryBuilder('c')
            ->andWhere('c.status <> :status')
            ->setParameter('status', "Prospect")
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Customer[] Returns customers without coordinates
     */
    public function findCustomersWithoutCoordinates()
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.latitude IS NULL OR c.longitude IS NULL')
            ->andWhere('c.status <> :status')
            ->setParameter('status', "Prospect")
            ->orderBy('c.created_date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Customer[] Returns non-prospect customers linked to a specific user
     */
    public function findAllNotProspectsByUser($userId)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.appointments', 'a')
            ->leftJoin('a.user', 'u')
            ->andWhere('c.status <> :status')
            ->andWhere('u.id = :user')
            ->setParameter('status', "Prospect")
            ->setParameter('user', $userId)
            ->orderBy('c.created_date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Customer[] Returns an array of Customer objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Customer
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
