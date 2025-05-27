<?php

namespace App\WhiteLabel\Repository\Client1;

use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

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
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, EntrepriseProfile::class);
    }

    public function paginateRecipes($page, PaginatorInterface $paginator, ?int $userId): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('e')->select('e, COUNT(j.id) AS jobCount')
        ->leftJoin('e.jobListings', 'j')
        ->groupBy('e.id')
        ->addOrderBy('e.id', 'DESC');

        return $paginator->paginate(
            $queryBuilder,
            $page,
            20,
            []
        );
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function countStatus(?string $status): int
    {
        if (!$status || $status == 'ALL') {
            return $this->countAll();
        }
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function paginateEntrepriseProfiles($page, string $status = null): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->select('e, COUNT(j.id) AS jobCount, e.isPremium AS premium')
            ->leftJoin('e.jobListings', 'j') 
            ->groupBy('e.id') 
            ->addOrderBy('e.id', 'DESC');
            
        if ($status && $status != 'ALL') {
            $queryBuilder
                ->andWhere('e.status = :status')
                ->setParameter('status', $status);
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            20,
            []
        );
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
            ->innerJoin('e.boost', 'b') 
            ->innerJoin('b.boostVisibilities', 'bv') 
            ->andWhere('bv.endDate < :now')        
            ->setParameter('now', new \DateTime())
            ->getQuery()                          
            ->getResult(); 
    }
}
