<?php

namespace App\Repository\Finance;

use App\Entity\User;
use App\Entity\Finance\Employe;
use App\Data\Finance\SearchData;
use App\Entity\Finance\Simulateur;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Simulateur>
 *
 * @method Simulateur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Simulateur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Simulateur[]    findAll()
 * @method Simulateur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SimulateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginatorInterface)
    {
        parent::__construct($registry, Simulateur::class);
    }

    public function findSimulateursNotDeletedForEmploye(Employe $employe)
    {
        $qb = $this->createQueryBuilder('s'); 
        $qb->where('s.employe = :employe')
        ->andWhere($qb->expr()->orX(
            $qb->expr()->isNull('s.status'),
            $qb->expr()->eq('s.status', ':statusValid')
        ))
        ->setParameter('employe', $employe)
        // ->setParameter('statusValid', Simulateur::STATUS_VALID)
        ->orderBy('s.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function findSimulationsWithEmploye()
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.employe IS NOT NULL');

        return $qb->getQuery()->getResult();
    }

    public function findSearch(SearchData $searchData): PaginationInterface
    {
        $qb = $this
        ->createQueryBuilder('s')
        ->select('s', 'e', 'u', '(s.salaireNet * s.taux) AS montant')
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

        if(!empty($searchData->status)){
            $qb = $qb
                ->andWhere('s.statusFinance = :status')
                ->setParameter('status', $searchData->status)
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


   /**
    * @return Simulateur[] Returns an array of Simulateur objects
    */
   public function findSimulateursByUser(User $user): array
   {
        if(!$user->getEmploye() instanceof Employe){
            return [];
        }

       return $this->createQueryBuilder('s')
           ->andWhere('s.employe = :val')
           ->setParameter('val', $user->getEmploye())
           ->orderBy('s.id', 'DESC')
           ->getQuery()
           ->getResult()
       ;
   }

//    public function findOneBySomeField($value): ?Simulateur
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
