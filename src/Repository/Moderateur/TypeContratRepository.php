<?php

namespace App\Repository\Moderateur;

use App\Entity\Moderateur\TypeContrat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeContrat>
 *
 * @method TypeContrat|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeContrat|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeContrat[]    findAll()
 * @method TypeContrat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeContratRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeContrat::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

//    /**
//     * @return TypeContrat[] Returns an array of TypeContrat objects
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

//    public function findOneBySomeField($value): ?TypeContrat
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
