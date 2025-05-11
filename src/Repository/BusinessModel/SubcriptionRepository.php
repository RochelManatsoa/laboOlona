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

    //    /**
    //     * @return Subcription[] Returns an array of Subcription objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Subcription
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
