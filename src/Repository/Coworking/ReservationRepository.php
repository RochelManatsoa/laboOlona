<?php

namespace App\Repository\Coworking;

use App\Entity\Coworking\Reservation;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Reservation>
 *
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Reservation::class);
    }
    
    public function paginateRecipes($page): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('r')->select('r');
        $queryBuilder->addOrderBy('r.id', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            10,
            [
                'distinct' => false,
                'shortFieldAllowList' => ['id', 'fullName', 'date', 'status', 'createdAt'],
            ]
        );
    }

    public function findLatestReservationByUniqueEmail()
    {
        $subQuery = $this->createQueryBuilder('sub')
            ->select('MAX(sub.id)')
            ->groupBy('sub.email');

        return $this->createQueryBuilder('r')
            ->select('r') 
            ->where('r.id IN (' . $subQuery->getDQL() . ')')
            ->getQuery()
            ->getResult();
    }
}
