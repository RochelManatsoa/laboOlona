<?php

namespace App\Repository\Blog;

use App\Entity\Blog\Category;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Category::class);
    }
    
    public function findPublished(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isPublished = true')
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function paginateCategories($page): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('c')->select('c');
        $queryBuilder
            ->addOrderBy('c.createdAt', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            10,
            []
        );
    }
}
