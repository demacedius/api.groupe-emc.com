<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 *
 * @method Appointment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Appointment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Appointment[]    findAll()
 * @method Appointment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Appointment $entity, bool $flush = true): void
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
    public function remove(Appointment $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @return Appointment[] Returns an array of Appointment objects
     */
    public function findAllOpenedAppointments()
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.closed = false')
            ->orderBy('a.createdDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Appointment[] Returns an array of Appointment objects
     */
    public function findAllOpenedAppointmentsByUser($userId)
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->andWhere('a.closed = false')
            ->andWhere('u.id = :user')
            ->setParameter('user', $userId)
            ->orderBy('a.createdDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Appointment[] Returns an array of Appointment objects
     */
    public function findAllOpenedAppointmentsByUsers(array $userIds)
    {
        if (empty($userIds)) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->andWhere('a.closed = false')
            ->andWhere('u.id IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->orderBy('a.createdDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Appointment[] Returns an array of Appointment objects
     */
    public function findTodayAppointment($user, $customer)
    {
        $today = new \Datetime();

        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->leftJoin('a.customer', 'c')
            ->andWhere('a.closed = false')
            ->andWhere('u = :user')
            ->andWhere('c = :customer')
            ->andWhere('a.createdDate LIKE :today')
            ->setParameter('user', $user)
            ->setParameter('customer', $customer)
            ->setParameter('today', $today->format('Y-m-d') . '%')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param int $month
     * @param int $year
     * 
     * @return Sell[]
     */
    public function findCurrentMonthAppointments($month = null, $year = null)
    {
        if ($month === null) {
            $month = (int) date('m');
        }
    
        if ($year === null) {
            $year = (int) date('Y');
        }
    
        $startDate = new \DateTimeImmutable("$year-$month-01T00:00:00");
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('a');

        $qb->select('a')
            ->where('a.createdDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('end', $endDate->format('Y-m-d H:i:s'));

        return $qb->getQuery()
                    ->getResult();
    }

    /**
     * @param int $month
     * @param int $year
     * 
     * @return Appointment[]
     */
    public function findCurrentMonthAppointmentsByUser($userId, $month = null, $year = null)
    {
        // If no month/year provided, use the last month with sales from SellRepository
        if ($month === null || $year === null) {
            $sellRepo = $this->getEntityManager()->getRepository('App\Entity\Sell');
            $lastMonth = $sellRepo->getLastMonthWithSales();
            $month = $lastMonth['month'];
            $year = $lastMonth['year'];
        }
        
        $startDate = new \DateTimeImmutable("$year-$month-01T00:00:00");
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);
        
        $qb = $this->createQueryBuilder('a');

        $qb->select('a')
            ->leftJoin('a.user', 'u')
            ->where('u.id = :id')
            ->andWhere('a.createdDate BETWEEN :start AND :end')
            ->setParameter('id', $userId)
            ->setParameter('start', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('end', $endDate->format('Y-m-d H:i:s'));

        return $qb->getQuery()
                    ->getResult();
    } 

    /**
     * Récupère les prospects replacés qui doivent réapparaître aujourd'hui
     * 
     * @return Appointment[]
     */
    public function findReplacedAppointmentsForToday()
    {
        $today = new \DateTime();
        $startOfDay = clone $today;
        $startOfDay->setTime(0, 0, 0);
        $endOfDay = clone $today;
        $endOfDay->setTime(23, 59, 59);

        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :replacer_status')
            ->andWhere('a.replacementDate BETWEEN :start AND :end')
            ->setParameter('replacer_status', 'A REPLACER')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->orderBy('a.createdDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les prospects ouverts incluant ceux à replacer pour aujourd'hui
     * 
     * @return Appointment[]
     */
    public function findAllOpenedAppointmentsIncludingReplaced()
    {
        $today = new \DateTime();
        $startOfDay = clone $today;
        $startOfDay->setTime(0, 0, 0);
        $endOfDay = clone $today;
        $endOfDay->setTime(23, 59, 59);

        return $this->createQueryBuilder('a')
            ->andWhere('(a.closed = false OR (a.status = :replacer_status AND a.replacementDate BETWEEN :start AND :end))')
            ->setParameter('replacer_status', 'A REPLACER')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->orderBy('a.createdDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /*
    public function findOneBySomeField($value): ?Appointment
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
