<?php

namespace App\Repository\Marketing;

use App\Entity\Marketing\Lead;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Lead>
 *
 * @method Lead|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lead|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lead[]    findAll()
 * @method Lead[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Lead::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function countStatus(?string $status): int
    {
        if (!$status || $status == 'ALL') {
            return $this->countAll();
        }
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function paginateLeads($page, string $status = null): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->select('l, s')
            ->leftJoin('l.source', 's') 
            ->groupBy('l.id') 
            ->addOrderBy('l.id', 'DESC');
            
        if ($status && $status != 'ALL') {
            $queryBuilder
                ->andWhere('l.status = :status')
                ->setParameter('status', $status);
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            20,
            []
        );
    }
}
