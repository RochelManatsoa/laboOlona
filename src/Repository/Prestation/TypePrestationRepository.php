<?php

namespace App\Repository\Prestation;

use App\Entity\Prestation\TypePrestation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypePrestation>
 *
 * @method TypePrestation|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypePrestation|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypePrestation[]    findAll()
 * @method TypePrestation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypePrestationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypePrestation::class);
    }

//    /**
//     * @return TypePrestation[] Returns an array of TypePrestation objects
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

//    public function findOneBySomeField($value): ?TypePrestation
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
