<?php

namespace App\Repository\Marketing;

use App\Entity\Marketing\Source;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Source>
 *
 * @method Source|null find($id, $lockMode = null, $lockVersion = null)
 * @method Source|null findOneBy(array $criteria, array $orderBy = null)
 * @method Source[]    findAll()
 * @method Source[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Source::class);
    }

    public function paginateLeads($page): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('s, COUNT(l.id) AS leadCount')
            ->leftJoin('s.leads', 'l') 
            ->groupBy('s.id') 
            ->addOrderBy('s.id', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            20,
            []
        );
    }
}
