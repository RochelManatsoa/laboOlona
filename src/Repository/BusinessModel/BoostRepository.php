<?php

namespace App\Repository\BusinessModel;

use App\Entity\CandidateProfile;
use App\Entity\BusinessModel\Boost;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Boost>
 *
 * @method Boost|null find($id, $lockMode = null, $lockVersion = null)
 * @method Boost|null findOneBy(array $criteria, array $orderBy = null)
 * @method Boost[]    findAll()
 * @method Boost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BoostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Boost::class);
    }

    public function findBoostByCandidate(CandidateProfile $candidateProfile): ?Boost
    {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.candidateProfiles', 'cp')
            ->where('cp = :candidateProfile')
            ->setParameter('candidateProfile', $candidateProfile)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Boost[] Returns an array of Boost objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Boost
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
