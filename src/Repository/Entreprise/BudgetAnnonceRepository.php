<?php

namespace App\Repository\Entreprise;

use App\Entity\Entreprise\BudgetAnnonce;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BudgetAnnonce>
 *
 * @method BudgetAnnonce|null find($id, $lockMode = null, $lockVersion = null)
 * @method BudgetAnnonce|null findOneBy(array $criteria, array $orderBy = null)
 * @method BudgetAnnonce[]    findAll()
 * @method BudgetAnnonce[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BudgetAnnonceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BudgetAnnonce::class);
    }

//    /**
//     * @return BudgetAnnonce[] Returns an array of BudgetAnnonce objects
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

//    public function findOneBySomeField($value): ?BudgetAnnonce
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
