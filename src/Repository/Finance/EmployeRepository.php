<?php

namespace App\Repository\Finance;

use App\Entity\Finance\Employe;
use App\Data\Finance\SearchData;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Employe>
 *
 * @method Employe|null find($id, $lockMode = null, $lockVersion = null)
 * @method Employe|null findOneBy(array $criteria, array $orderBy = null)
 * @method Employe[]    findAll()
 * @method Employe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmployeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginatorInterface)
    {
        parent::__construct($registry, Employe::class);
    }

    public function findSearch(SearchData $searchData): PaginationInterface
    {
        $qb = $this
        ->createQueryBuilder('e')
        ->select('e, u.id, u.nom, COUNT(s.id) AS nombreDeSimulateurs, SUM(s.salaireNet * s.taux) AS montantTotal')
        ->join('e.simulateurs', 's')
        ->join('e.user', 'u')
        ->groupBy('u.id');
        ;

        if(!empty($searchData->q)){
            $qb = $qb
                ->andWhere('u.nom LIKE :nom')
                ->andWhere('u.prenom LIKE :prenom')
                ->setParameter('nom', "%{$searchData->q}%")
                ->setParameter('prenom', "%{$searchData->q}%")
            ;
        }

        if(!empty($searchData->status)){
            $qb = $qb
                ->andWhere('s.statusFinance LIKE :status')
                ->setParameter('status', "%{$searchData->status}%")
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
        // dd($query->getResult());

        return $this->paginatorInterface->paginate(
            $query,
            $searchData->page,
            10
        );
    }

//    /**
//     * @return Employe[] Returns an array of Employe objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Employe
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
