<?php

namespace App\WhiteLabel\Repository\Client1\Entreprise;

use App\Data\SearchData;
use App\Data\V2\JobOfferData;
use Doctrine\ORM\EntityRepository;
use App\Data\Annonce\AnnonceSearchData;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use Knp\Component\Pager\Pagination\PaginationInterface;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<JobListing>
 *
 * @method JobListing|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobListing|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobListing[]    findAll()
 * @method JobListing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobListingRepository extends ServiceEntityRepository
{

    public function __construct(
        ManagerRegistry $registry, 
        private PaginatorInterface $paginator,
    )
    {
        parent::__construct($registry, JobListing::class);
    }

    public function paginateRecipes($page, PaginatorInterface $paginator, ?int $userId): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('j')
        ->select('j, COUNT(v.id) AS views, COUNT(a.id) AS offers')
        ->leftJoin('j.annonceVues', 'v')
        ->leftJoin('j.applications', 'a')
        ->groupBy('j.id')
        ->addOrderBy('j.id', 'DESC');

        return $paginator->paginate(
            $queryBuilder,
            $page,
            20,
            []
        );
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.status = :pending')
            ->setParameter('pending', JobListing::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAllByEntreprise(EntrepriseProfile $entrepriseProfile): int
    {
        return (int) $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->andWhere('j.entreprise = :entreprise')
            ->setParameter('entreprise', $entrepriseProfile)
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function countStatusByEntreprise(EntrepriseProfile $entrepriseProfile, ?string $status): int
    {
        if (!$status || $status == 'ALL') {
            return $this->countAllByEntreprise($entrepriseProfile);
        }
        return (int) $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.status = :status')
            ->andWhere('j.entreprise = :entreprise')
            ->setParameter('status', $status)
            ->setParameter('entreprise', $entrepriseProfile)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function paginateJobListingsEntrepriseProfiles(EntrepriseProfile $entrepriseProfile, $page, string $status = null, PaginatorInterface $paginator): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('j')
            ->select('j, COUNT(a.id) AS applicationCount')
            ->leftJoin('j.applications', 'a') 
            ->groupBy('j.id') 
            ->addOrderBy('j.id', 'DESC')
            ->andWhere('j.entreprise = :entreprise')
            ->setParameter('entreprise', $entrepriseProfile);

        if ($status && $status != 'ALL') {
            $queryBuilder
                ->andWhere('j.status = :status')
                ->setParameter('status', $status);
        }

        return $paginator->paginate(
            $queryBuilder,
            $page,
            20,
            []
        );
    }

    public function paginateJobListings(PaginatorInterface $paginator, ?string $status = null, $page = 1, $size = 10): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('j')
            ->select('j')
            ->leftJoin('j.applications', 'a') 
            ->groupBy('j.id') 
            ->addOrderBy('j.id', 'DESC');

            if($status){
                $queryBuilder->andWhere('j.status = :status')
                ->setParameter('status', $status);
            }

        return $paginator->paginate(
            $queryBuilder,
            $page,
            $size,
            []
        );
    }
    
    public function findAllOrderedByIdDesc()
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    public function findAllJobListingPublished()
    {
        $queryBuilder = $this->createQueryBuilder('j');

        $orConditions = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('j.status', ':statusValid'),
            $queryBuilder->expr()->eq('j.status', ':statusFeatured')
        );

        $query = $queryBuilder
            ->andWhere('j.isGenerated = :isGenerated')
            ->andWhere($orConditions)
            ->setParameter('statusValid', JobListing::STATUS_PUBLISHED)
            ->setParameter('statusFeatured', JobListing::STATUS_FEATURED)
            ->setParameter('isGenerated', true)
            ->orderBy('j.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }
    
    public function findPublishedJobListing()
    {
        $query = $this->createQueryBuilder('j')
            ->andWhere('j.isGenerated = :isGenerated')
            ->andWhere('j.status = :statusValid')
            ->setParameter('statusValid', JobListing::STATUS_PUBLISHED)
            ->setParameter('isGenerated', true)
            ->orderBy('j.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }
    
    public function findPremiumJobListing()
    {
        $query = $this->createQueryBuilder('j')
            ->andWhere('j.isGenerated = :isGenerated')
            ->andWhere('j.status = :statusFeatured')
            ->setParameter('statusFeatured', JobListing::STATUS_FEATURED)
            ->setParameter('isGenerated', true)
            ->orderBy('j.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }
    
    public function findFeaturedJobListing()
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.status = :published') 
            ->setParameter('published', JobListing::STATUS_FEATURED)
            ->orderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    public function findJobListingForShortDescription()
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.status = :published') 
            ->andWhere('j.isGenerated IS NULL OR j.isGenerated = :isGenerated')
            ->setParameter('published', JobListing::STATUS_PUBLISHED)
            ->setParameter('isGenerated', false)
            ->orderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findJoblistingsForReport()
    {
        $queryBuilder = $this->createQueryBuilder('j');
    
        $orConditions = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('j.status', ':statusValid'),
            $queryBuilder->expr()->eq('j.status', ':statusFeatured')
        );
    
        $isGeneratedConditions = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('j.isGenerated', ':isGeneratedFalse'),
            $queryBuilder->expr()->isNull('j.isGenerated')
        );
    
        $query = $queryBuilder
            ->andWhere($isGeneratedConditions)
            ->andWhere($orConditions)
            ->setParameter('statusValid', JobListing::STATUS_PUBLISHED)
            ->setParameter('statusFeatured', JobListing::STATUS_FEATURED)
            ->setParameter('isGeneratedFalse', false)
            ->setMaxResults(10)
            ->orderBy('j.id', 'DESC')
            ->getQuery();
            
        return $query->getResult();
    }    

    public function findJoblistingsForNotification()
    {
        $queryBuilder = $this->createQueryBuilder('j');

        $orConditions = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('j.status', ':statusValid'),
            $queryBuilder->expr()->eq('j.status', ':statusFeatured')
        );

        $updatedAtCondition = $queryBuilder->expr()->gte(
            'j.updatedAt',
            ':yesterday'
        );

        $isNotifiedCondition = $queryBuilder->expr()->eq('j.isNotified', ':isNotifiedFalse');

        $query = $queryBuilder
            ->andWhere($orConditions)
            ->andWhere($updatedAtCondition)
            ->andWhere($isNotifiedCondition)
            ->setParameter('statusValid', JobListing::STATUS_PUBLISHED)
            ->setParameter('statusFeatured', JobListing::STATUS_FEATURED)
            ->setParameter('yesterday', new \DateTime('-1 day'))
            ->setParameter('isNotifiedFalse', false)
            ->orderBy('j.id', 'DESC')
            ->getQuery();

        return $query->getResult();
    } 

    public function findJoblistingsForPostFacebook()
    {
        $queryBuilder = $this->createQueryBuilder('j');

        $now = new \DateTimeImmutable(); // maintenant
        $oneDayAgo = $now->sub(new \DateInterval('P1D')); // il y a 1 jour

        $query = $queryBuilder
            ->andWhere('j.dateCreation BETWEEN :oneDayAgo AND :now')
            ->andWhere('j.status = :statusFeatured')
            ->andWhere('j.isPublishedOnFacebook = :isPublishedOnFacebookFalse')
            ->andWhere('j.shortDescription IS NOT NULL')
            ->setParameter('oneDayAgo', $oneDayAgo)
            ->setParameter('now', $now)
            ->setParameter('statusFeatured', JobListing::STATUS_FEATURED)
            ->setParameter('isPublishedOnFacebookFalse', false)
            ->orderBy('j.id', 'DESC')
            ->getQuery();

        return $query->getResult();
    }



    /**
     * @param EntrepriseProfile $entreprise
     * @param string $status
     * @return JobListing[]
     */
    public function findByEntrepriseAndStatus(EntrepriseProfile $entreprise, string $status): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.entreprise = :entreprise')
            ->andWhere('j.status = :status')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    public function findSearch(AnnonceSearchData $searchData): PaginationInterface
    {
        $qb = $this
            ->createQueryBuilder('j')
            ->select('j, u.id, u.nom, e.nom, COUNT(DISTINCT s.id) AS nombreDeCompetences, COUNT(DISTINCT a.id) AS nombreDeCandidatures')
            ->leftJoin('j.competences', 's')
            ->leftJoin('j.applications', 'a')
            ->leftJoin('j.secteur', 'sect')
            ->leftJoin('j.typeContrat', 't')
            ->leftJoin('j.budgetAnnonce', 'b')
            ->leftJoin('j.entreprise', 'e')
            ->join('e.entreprise', 'u')
            ->groupBy('u.id')
            ->orderBy('j.id', 'DESC');


        if (!empty($searchData->q)) {
            $words = explode(' ', $searchData->q);
            foreach ($words as $word) {
                $word = trim($word);
                if (!empty($word)) {
                    $qb->andWhere('u.nom LIKE :word OR u.prenom LIKE :word OR u.email LIKE :word OR j.titre LIKE :word')
                        ->setParameter('word', "%{$word}%");
                }
            }
        }

        if (!empty($searchData->status)) {
            $qb = $qb
                ->andWhere('j.status LIKE :status')
                ->setParameter('status', "%{$searchData->status}%");
        }

        if ($searchData->tarif === 1) {
            $qb = $qb
                ->andWhere('b.id IS NOT NULL');
        } elseif ($searchData->tarif === 0) {
            $qb = $qb
                ->andWhere('b.id IS NULL');
        }

        if ($searchData->secteurs === 1) {
            $qb = $qb
                ->andWhere('sect.id IS NOT NULL');
        } elseif ($searchData->secteurs === 0) {
            $qb = $qb
                ->andWhere('j.secteur IS EMPTY');
        }

        if ($searchData->entreprise instanceof EntrepriseProfile) {
            $qb = $qb
                ->andWhere('e.id = :id')
                ->setParameter('id', "{$searchData->entreprise->getId()}");
        }

        $query =  $qb->getQuery();
        // dd($query->getResult());

        return $this->paginator->paginate(
            $query,
            $searchData->page,
            20
        );
    }

    public function findJobListingsByEntreprise(EntrepriseProfile $recruiter)
    {
        return $this->createQueryBuilder('j')
            ->where('j.entreprise = :entreprise')
            ->andWhere('j.status != :deletedStatus')
            ->setParameter('entreprise', $recruiter)
            ->setParameter('deletedStatus', JobListing::STATUS_DELETED)
            ->orderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findEngineSearch(SearchData $searchData): PaginationInterface
    {
        $qb = $this
            ->createQueryBuilder('j')
            ->select('j, u.id, u.nom, e.nom, COUNT(DISTINCT s.id) AS nombreDeCompetences, COUNT(DISTINCT a.id) AS nombreDeCandidatures')
            ->leftJoin('j.competences', 's')
            ->leftJoin('j.applications', 'a')
            ->leftJoin('j.secteur', 'sect')
            ->leftJoin('j.typeContrat', 't')
            ->leftJoin('j.budgetAnnonce', 'b')
            ->leftJoin('j.entreprise', 'e')
            ->join('e.entreprise', 'u')
            ->groupBy('u.id')
            ->orderBy('j.id', 'DESC');


        if (!empty($searchData->q)) {
            $queryString = $searchData->q;
            $words = explode(' ', $queryString);
            foreach ($words as $word) {
                $word = trim($word);
                if (!empty($word)) {
                    $qb->andWhere('u.nom LIKE :word OR u.prenom LIKE :word OR u.email LIKE :word OR j.titre LIKE :word')
                        ->setParameter('word', "%{$word}%");
                }
            }
        }

        if ($searchData->secteurs === 1) {
            $qb = $qb
                ->andWhere('sect.id IS NOT NULL');
        } elseif ($searchData->secteurs === 0) {
            $qb = $qb
                ->andWhere('j.secteur IS EMPTY');
        }

        $query =  $qb->getQuery();
        // dd($query->getResult());

        return $this->paginator->paginate(
            $query,
            $searchData->page,
            20
        );
    }

    public function candidateSearch(JobOfferData $searchData): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('j');
        $orConditions = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('j.status', ':statusValid'),
            $queryBuilder->expr()->eq('j.status', ':statusFeatured')
        );
        
        $qb = $queryBuilder
            ->select('j, COUNT(DISTINCT a.id) AS nombreDeCandidatures')
            ->leftJoin('j.competences', 's')
            ->leftJoin('j.applications', 'a')
            ->leftJoin('j.secteur', 'sect')
            ->leftJoin('j.typeContrat', 't')
            ->leftJoin('j.budgetAnnonce', 'b')
            ->leftJoin('j.entreprise', 'e')
            ->join('e.entreprise', 'u')
            ->andWhere($orConditions)
            ->setParameter('statusValid', JobListing::STATUS_PUBLISHED)
            ->setParameter('statusFeatured', JobListing::STATUS_FEATURED)
            ->groupBy('u.id')
            ->orderBy('j.id', 'DESC')
            ;


        if (!empty($searchData->q)) {
            $queryString = $searchData->q;
            $words = explode(' ', $queryString);
            foreach ($words as $word) {
                $word = trim($word);
                if (!empty($word)) {
                    $qb->andWhere('s.nom LIKE :word OR sect.nom LIKE :word OR j.description LIKE :word OR j.titre LIKE :word')
                        ->setParameter('word', "%{$word}%");
                }
            }
        }

        $query =  $qb->getQuery();
        // dd($query->getResult());

        return $this->paginator->paginate(
            $query,
            $searchData->page,
            20
        );
    }

    public function findExpiredPremium()
    {
        return $this->createQueryBuilder('j')
            ->innerJoin('j.boost', 'b') 
            ->innerJoin('b.boostVisibilities', 'bv') 
            ->andWhere('bv.endDate < :now')        
            ->setParameter('now', new \DateTime())
            ->getQuery()                          
            ->getResult(); 
    }

    public function findValidJobListings()
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.status = :statusValid')
            ->setParameter('statusValid', JobListing::STATUS_PUBLISHED)
            ->getQuery()
            ->getResult();
    }
}
