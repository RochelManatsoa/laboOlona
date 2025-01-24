<?php

namespace App\Repository\Facebook;

use App\Entity\Facebook\ContestEntry;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<ContestEntry>
 *
 * @method ContestEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContestEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContestEntry[]    findAll()
 * @method ContestEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContestEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, ContestEntry::class);
    }

    public function paginateContestEntries($page): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('c')->select('c');
        $queryBuilder
            ->addOrderBy('c.submittedAt', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            10,
            []
        );
    }

    public function findLastByUser(User $user): ?ContestEntry
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user') 
            ->setParameter('user', $user)
            ->orderBy('c.submittedAt', 'DESC')
            ->setMaxResults(1) 
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
