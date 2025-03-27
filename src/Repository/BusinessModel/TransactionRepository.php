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

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :pending')
            ->setParameter('pending', Transaction::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
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
            ->leftJoin('t.command', 'o')
            ->leftJoin('u.entrepriseProfile', 'e')
            ->leftJoin('u.candidateProfile', 'c')
            ->orderBy('t.id', 'DESC')
        ;

        if (!empty($searchData->status)) {
            $qb = $qb
                ->andWhere('t.status LIKE :status')
                ->setParameter('status', "%{$searchData->status}%");
        }

        if (!empty($searchData->q)) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('u.nom', ':q'),
                $qb->expr()->like('u.prenom', ':q'),
                $qb->expr()->like('u.email', ':q'),
                $qb->expr()->like('e.nom', ':q'),
                $qb->expr()->like('c.titre', ':q'),
                $qb->expr()->like('p.name', ':q'),
                $qb->expr()->like('o.orderNumber', ':q'),
                $qb->expr()->like('t.reference', ':q')
            ))
            ->setParameter('q', '%' . $searchData->q . '%');
        }
       
        $query =  $qb->getQuery();

        return $this->paginator->paginate(
            $query,
            $searchData->page,
            20
        );
    }
}
