<?php

namespace App\Repository\BusinessModel;

use App\Entity\BusinessModel\Subcription;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Subcription>
 */
class SubcriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator,)
    {
        parent::__construct($registry, Subcription::class);
    }

    public function paginateSubcriptions($page, string $type = null): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->groupBy('s.id') 
            ->addOrderBy('s.id', 'DESC');
            
        if ($type && $type != 'ALL') {
            $queryBuilder
                ->andWhere('s.type = :type')
                ->setParameter('type', $type);
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            20,
            []
        );
    }
    
    public function findAbonnementsToRelance(string $targetDate, int $relanceNumber): array
    {
        $start = new \DateTime($targetDate . ' 00:00:00');
        $end = new \DateTime($targetDate . ' 23:59:59');
    
        return $this->createQueryBuilder('s')
            ->andWhere('s.endDate BETWEEN :start AND :end')
            ->andWhere('s.relance = :relanceLevel')
            ->andWhere('s.active = true')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('relanceLevel', $relanceNumber - 1)
            ->getQuery()
            ->getResult();
    }
    

    public function findExpiredActives(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.endDate < :now')
            ->andWhere('s.active = true')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}
