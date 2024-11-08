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
    
    public function getAffiliateToolsByTag($tag)
    {
        $qb = $this->createQueryBuilder('a')
           ->join('a.tags', 't') 
           ->where('t.id = :tagId') 
           ->andWhere('a.type = :status') 
           ->andWhere('a.image IS NOT NULL')
           ->setParameter('status', 'publish')
           ->setParameter('tagId', $tag->getId()); 
           
        return $qb->getQuery()->getResult();
    }
    
    public function getAffiliateToolsByCategory($category)
    {
        $qb = $this->createQueryBuilder('a')
           ->join('a.categories', 'c') 
           ->where('c.id = :catId') 
           ->andWhere('a.type = :status') 
           ->andWhere('a.image IS NOT NULL')
           ->setParameter('status', 'publish')
           ->setParameter('catId', $category->getId()); 
           
        return $qb->getQuery()->getResult();
    }
}
