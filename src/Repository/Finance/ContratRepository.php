<?php

namespace App\Repository\Finance;

use App\Entity\Finance\Contrat;
use App\Entity\Finance\Employe;
use App\Data\Finance\SearchData;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Contrat>
 *
 * @method Contrat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Contrat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Contrat[]    findAll()
 * @method Contrat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContratRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginatorInterface)
    {
        parent::__construct($registry, Contrat::class);
    }

    public function findSearch(SearchData $searchData): PaginationInterface
    {
        $qb = $this
        ->createQueryBuilder('c')
        ->select('c', 's', 'e', 'u', '(s.salaireNet * s.taux) AS montant')
        ->join('c.simulateur', 's')
        ->join('s.employe', 'e')
        ->join('e.user', 'u');
        ;

        if($searchData->employe instanceof Employe){
            $qb = $qb
                ->andWhere('e.id = :id')
                ->setParameter('id', $searchData->employe->getId())
            ;
        }

        if(!empty($searchData->q)){
            $qb = $qb
                ->andWhere('u.nom LIKE :nom')
                ->setParameter('nom', "%{$searchData->q}%")
            ;
        }

        if(!empty($searchData->statusDemande)){
            $qb = $qb
                ->andWhere('c.status = :status')
                ->setParameter('status', $searchData->statusDemande)
            ;
        }

        if(!empty($searchData->type)){
            $qb = $qb
                ->andWhere('u.type LIKE :type')
                ->setParameter('type', "%{$searchData->type}%")
            ;
        }

        if(!empty($searchData->salaires)){
            switch ($searchData->salaires) {
                case 'more4':
                    $qb = $qb
                        ->andWhere('(s.salaireNet * s.taux) >= :salaires')
                        ->setParameter('salaires', 4000000)
                    ;
                    break;

                case 'bet4and3':
                    $qb = $qb
                        ->andWhere('(s.salaireNet * s.taux) >= :minSal')
                        ->andWhere('(s.salaireNet * s.taux) <= :maxSal')
                        ->setParameter('minSal', 3000000)
                        ->setParameter('maxSal', 4000000);
                    break;
                    
                case 'bet3and2':
                    $qb = $qb
                        ->andWhere('(s.salaireNet * s.taux) >= :minSal')
                        ->andWhere('(s.salaireNet * s.taux) <= :maxSal')
                        ->setParameter('minSal', 2000000)
                        ->setParameter('maxSal', 3000000);
                    break;
                    
                case 'bet2and1':
                    $qb = $qb
                        ->andWhere('(s.salaireNet * s.taux) >= :minSal')
                        ->andWhere('(s.salaireNet * s.taux) <= :maxSal')
                        ->setParameter('minSal', 2000000)
                        ->setParameter('maxSal', 1000000);
                    break;
                    
                case 'less1':
                    $qb = $qb
                        ->andWhere('(s.salaireNet * s.taux) <= :salaires')
                        ->setParameter('salaires', 1000000)
                    ;
                    break;
                
                default:
                    break;
            }
        }

        $query =  $qb->getQuery();

        return $this->paginatorInterface->paginate(
            $query,
            $searchData->page,
            10
        );
    }

//    /**
//     * @return Contrat[] Returns an array of Contrat objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Contrat
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
