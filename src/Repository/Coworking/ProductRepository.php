<?php

namespace App\Repository\Coworking;

use App\Entity\Coworking\Product;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Product::class);
    }
    
    public function paginateRecipes($page, ?int $userId): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('p')->select('p');
        $queryBuilder->addOrderBy('p.id', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            20,
            [
                'distinct' => false,
                'shortFieldAllowList' => ['id', 'name', 'price', 'createdAt', 'updatedAt', 'stock', 'status'],
            ]
        );
    }
}
