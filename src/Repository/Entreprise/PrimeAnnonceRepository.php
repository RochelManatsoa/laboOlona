<?php

namespace App\Repository\Entreprise;

use App\Entity\Entreprise\PrimeAnnonce;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrimeAnnonce>
 *
 * @method PrimeAnnonce|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrimeAnnonce|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrimeAnnonce[]    findAll()
 * @method PrimeAnnonce[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrimeAnnonceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrimeAnnonce::class);
    }

//    /**
//     * @return PrimeAnnonce[] Returns an array of PrimeAnnonce objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PrimeAnnonce
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
