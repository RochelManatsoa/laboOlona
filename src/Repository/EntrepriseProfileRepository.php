<?php

namespace App\Repository;

use App\Entity\EntrepriseProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EntrepriseProfile>
 *
 * @method EntrepriseProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method EntrepriseProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method EntrepriseProfile[]    findAll()
 * @method EntrepriseProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EntrepriseProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EntrepriseProfile::class);
    }

    public function findTopRanked(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e, COUNT(j.id) as HIDDEN nbr_annonces')
            ->leftJoin('e.jobListings', 'j')
            ->andWhere('e.status = :statusFeatured')
            ->setParameter('statusFeatured', EntrepriseProfile::STATUS_PREMIUM)
            ->groupBy('e')
            ->orderBy('nbr_annonces', 'DESC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();
    }
    
    public function findAllJobListingPublished()
    {
        $queryBuilder = $this->createQueryBuilder('e');

        $orConditions = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('e.status', ':statusValid'),
            $queryBuilder->expr()->eq('e.status', ':statusFeatured')
        );

        $query = $queryBuilder
            ->andWhere($orConditions)
            ->setParameter('statusValid', EntrepriseProfile::STATUS_VALID)
            ->setParameter('statusFeatured', EntrepriseProfile::STATUS_PREMIUM)
            ->orderBy('e.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }
    
    public function findValidCompany()
    {
        $query = $this->createQueryBuilder('e')
            ->andWhere('e.status = :statusValid')
            ->setParameter('statusValid', EntrepriseProfile::STATUS_VALID)
            ->orderBy('e.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }
    
    public function findPremiumCompany()
    {
        $query = $this->createQueryBuilder('e')
            ->andWhere('e.status = :statusFeatured')
            ->setParameter('statusFeatured', EntrepriseProfile::STATUS_PREMIUM)
            ->orderBy('e.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }

    public function findExpiredPremium()
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.boostVisibility', 'b') 
            ->andWhere('b.endDate < :now')        
            ->setParameter('now', new \DateTime())
            ->getQuery()                          
            ->getResult(); 
    }
}
