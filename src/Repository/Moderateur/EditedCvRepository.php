<?php

namespace App\Repository\Moderateur;

use App\Entity\Moderateur\EditedCv;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EditedCv>
 *
 * @method EditedCv|null find($id, $lockMode = null, $lockVersion = null)
 * @method EditedCv|null findOneBy(array $criteria, array $orderBy = null)
 * @method EditedCv[]    findAll()
 * @method EditedCv[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EditedCvRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EditedCv::class);
    }


    public function findOneByCvLink($value): ?EditedCv
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.cvLink = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

//    public function findOneBySomeField($value): ?EditedCv
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
