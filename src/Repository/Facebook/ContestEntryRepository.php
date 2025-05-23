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

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function countStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function paginateContestEntries($page, string $status = null): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('c')->select('c');
        $queryBuilder
            ->addOrderBy('c.submittedAt', 'DESC');
        if ($status) {
            $queryBuilder
                ->andWhere('c.status = :status')
                ->setParameter('status', $status);
        }

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

    public function findEntryByStatus(string $status, int $maxResults = 200): array
    {
        $dateLimit = new \DateTimeImmutable('-6 hours'); 
    
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->andWhere('c.submittedAt < :dateLimit') 
            ->setParameter('status', $status)
            ->setParameter('dateLimit', $dateLimit)
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }
}
