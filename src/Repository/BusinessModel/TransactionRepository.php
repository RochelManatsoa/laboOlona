<?php

namespace App\Repository\BusinessModel;

use App\Entity\BusinessModel\Transaction;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use App\Data\BusinessModel\TransactionData;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator,)
    {
        parent::__construct($registry, Transaction::class);
    }

   /**
    * @return Transaction[] Returns an array of Transaction objects
    */
   public function findSearch(TransactionData $searchData): PaginationInterface
   {
        $qb = $this
            ->createQueryBuilder('t')
            ->select('t')
            ->leftJoin('t.typeTransaction', 'type')
            ->leftJoin('t.package', 'p')
            ->leftJoin('t.user', 'u')
            ->orderBy('t.id', 'DESC')
        ;

        if (!empty($searchData->status)) {
            $qb = $qb
                ->andWhere('t.status LIKE :status')
                ->setParameter('status', "%{$searchData->status}%");
        }

        if (!empty($searchData->reference)) {
            $qb = $qb
                ->andWhere('t.reference = :reference')
                ->setParameter('reference', "{$searchData->reference}");
        }
       
        $query =  $qb->getQuery();

        return $this->paginator->paginate(
            $query,
            $searchData->page,
            20
        );
    }
}
