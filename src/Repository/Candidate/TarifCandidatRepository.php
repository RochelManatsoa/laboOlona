<?php

namespace App\Repository\Candidate;

use App\Entity\Candidate\TarifCandidat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TarifCandidat>
 *
 * @method TarifCandidat|null find($id, $lockMode = null, $lockVersion = null)
 * @method TarifCandidat|null findOneBy(array $criteria, array $orderBy = null)
 * @method TarifCandidat[]    findAll()
 * @method TarifCandidat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TarifCandidatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TarifCandidat::class);
    }

//    /**
//     * @return TarifCandidat[] Returns an array of TarifCandidat objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TarifCandidat
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
