<?php

namespace App\Repository\Moderateur;

use App\Entity\Moderateur\Assignation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Assignation>
 *
 * @method Assignation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Assignation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Assignation[]    findAll()
 * @method Assignation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssignationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assignation::class);
    }

//    /**
//     * @return Assignation[] Returns an array of Assignation objects
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

//    public function findOneBySomeField($value): ?Assignation
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
