<?php

namespace App\Repository\Entreprise;

use App\Data\SearchData;
use App\Entity\EntrepriseProfile;
use App\Entity\Entreprise\JobListing;
use App\Data\Annonce\AnnonceSearchData;
use App\Data\V2\JobOfferData;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
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
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, JobListing::class);
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
}
