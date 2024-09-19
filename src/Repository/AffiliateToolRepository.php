<?php

namespace App\Repository;

use App\Entity\AffiliateTool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AffiliateTool>
 *
 * @method AffiliateTool|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffiliateTool|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffiliateTool[]    findAll()
 * @method AffiliateTool[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffiliateToolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffiliateTool::class);
    }

   /**
    * @return AffiliateTool[] Returns an array of AffiliateTool objects
    */
    public function findSearch($value, int $max = 12, int $offset = null): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.type = :val')
            ->andWhere('a.image IS NOT NULL')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults($max)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return AffiliateTool[] Returns an array of AffiliateTool objects
     */
    public function findByCategory($value, int $max = 12, int $offset = null): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.categories', 'c')
            ->andWhere('c.slug = :category')
            ->andWhere('a.type = :status') // Utilisation d'un paramètre pour 'publish'
            ->andWhere('a.image IS NOT NULL')
            ->setParameter('category', $value)
            ->setParameter('status', 'publish') // Définir la valeur de 'status'
            ->orderBy('a.id', 'ASC')
            ->setMaxResults($max)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return AffiliateTool[] Returns an array of AffiliateTool objects
     */
    public function findByTag($value, int $max = 12, int $offset = null): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.tags', 't')
            ->andWhere('t.slug = :tag')
            ->andWhere('a.type = :status') // Utilisation d'un paramètre pour 'publish'
            ->andWhere('a.image IS NOT NULL')
            ->setParameter('tag', $value)
            ->setParameter('status', 'publish') // Définir la valeur de 'status'
            ->orderBy('a.id', 'ASC')
            ->setMaxResults($max)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return AffiliateTool[] Returns an array of AffiliateTool objects
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

//    public function findOneBySomeField($value): ?AffiliateTool
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
