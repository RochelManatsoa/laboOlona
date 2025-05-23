<?php

namespace App\WhiteLabel\Repository\Client1\Candidate;

use App\WhiteLabel\Entity\Client1\Candidate\Applications;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Applications>
 *
 * @method Applications|null find($id, $lockMode = null, $lockVersion = null)
 * @method Applications|null findOneBy(array $criteria, array $orderBy = null)
 * @method Applications[]    findAll()
 * @method Applications[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApplicationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Applications::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.status = :pending')
            ->setParameter('pending', Applications::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByEntrepriseProfile(int $entrepriseId): int
    {
        return (int) $this->createQueryBuilder('a')
            ->join('a.annonce', 'j')
            ->join('a.candidat', 'c')
            ->join('j.entreprise', 'e')
            ->where('e.id = :entrepriseId')
            ->setParameter('entrepriseId', $entrepriseId)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByEntrepriseProfile(int $page, int $entrepriseId): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->join('a.annonce', 'j')
            ->join('a.candidat', 'c')
            ->join('j.entreprise', 'e')
            ->where('e.id = :entrepriseId')
            ->setParameter('entrepriseId', $entrepriseId)
            ->orderBy('a.dateCandidature', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            10,
            []
        );
    }

    public function findByCandidateProfile(CandidateProfile $candidat, int $page): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->select('a', 'j')
            ->join('a.annonce', 'j')
            ->where('a.candidat = :candidat')
            ->setParameter('candidat', $candidat)
            ->orderBy('a.dateCandidature', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            10,
            []
        );
    }


//    /**
//     * @return Applications[] Returns an array of Applications objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Applications
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
