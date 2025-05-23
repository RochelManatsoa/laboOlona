<?php

namespace App\Repository\Vues;

use App\Entity\Vues\AnnonceVues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnnonceVues>
 *
 * @method AnnonceVues|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnnonceVues|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnnonceVues[]    findAll()
 * @method AnnonceVues[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnnonceVuesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnnonceVues::class);
    }

//    /**
//     * @return AnnonceVues[] Returns an array of AnnonceVues objects
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

//    public function findOneBySomeField($value): ?AnnonceVues
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
