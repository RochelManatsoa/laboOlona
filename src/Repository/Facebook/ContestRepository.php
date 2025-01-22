<?php

namespace App\Repository\Facebook;

use App\Entity\Facebook\Contest;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Contest>
 *
 * @method Contest|null find($id, $lockMode = null, $lockVersion = null)
 * @method Contest|null findOneBy(array $criteria, array $orderBy = null)
 * @method Contest[]    findAll()
 * @method Contest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Contest::class);
    }

    public function paginateContests($page): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('c')->select('c');
        $queryBuilder
            ->addOrderBy('c.createdAt', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            10,
            []
        );
    }

//    public function paginateContests($value): ?Contest
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
