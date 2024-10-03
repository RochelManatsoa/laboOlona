<?php

namespace App\Repository\BusinessModel;

use App\Data\QuerySearchData;
use App\Entity\BusinessModel\Order;
use App\Service\User\UserService;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private PaginatorInterface $paginator,
        private UserService $userService,
    ) {
        parent::__construct($registry, Order::class);
    }

    public function filterByUser(QuerySearchData $searchData): PaginationInterface
    {
        $qb = $this
            ->createQueryBuilder('o')
            ->select('o')
            ->leftJoin('o.package', 'p')
            ->leftJoin('o.paymentMethod', 'type')
            ->andWhere('o.customer = :user')
            ->setParameter('user', $this->userService->getCurrentUser())  
            ->orderBy('o.id', 'DESC');

            if (!empty($searchData->q)) {
                $words = explode(' ', $searchData->q);
                foreach ($words as $word) {
                    $word = trim($word);
                    if (!empty($word)) {
                        $qb
                        ->andWhere('p.name LIKE :word OR p.description LIKE :word OR p.price LIKE :word')
                        ->setParameter('word', "%{$word}%");
                    }
                }
            }

        $query = $qb->getQuery();

        return $this->paginator->paginate(
            $query,
            $searchData->page,
            10
        );
    }
}
