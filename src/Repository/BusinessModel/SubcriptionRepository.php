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
        $start = new \DateTimeImmutable($targetDate . ' 00:00:00');
        $end = new \DateTimeImmutable($targetDate . ' 23:59:59');

        return $this->createQueryBuilder('s')
            ->andWhere('s.endDate BETWEEN :start AND :end')
            ->andWhere('s.relance = :relanceLevel')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('relanceLevel', $relanceNumber - 1)
            ->getQuery()
            ->getResult();
    }
    
    public function findExpiredActives(): array
    {
        $sevenDaysAgo = (new \DateTimeImmutable())->modify('-7 days');
    
        return $this->createQueryBuilder('s')
            ->andWhere('s.endDate <= :limitDate')
            ->andWhere('s.active = true')
            ->andWhere('s.relance = 2') // uniquement ceux à qui on a déjà envoyé J+3
            ->setParameter('limitDate', $sevenDaysAgo)
            ->getQuery()
            ->getResult();
    }
}
