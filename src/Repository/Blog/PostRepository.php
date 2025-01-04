<?php

namespace App\Repository\Blog;

use App\Entity\Blog\Category;
use App\Entity\Blog\Post;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Post::class);
    }

    public function lastPosts(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', Post::STATUS_PUBLISHED)
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }

    public function paginatePosts($page, ?Category $category = null): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('p')->select('p');
        $queryBuilder
            ->andWhere('p.status = :status')
            ->setParameter('status', Post::STATUS_PUBLISHED)
            ->addOrderBy('p.createdAt', 'DESC');
            
        if ($category) {
            $queryBuilder->andWhere('p.category = :category')->setParameter('category', $category);
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            10,
            []
        );
    }
}
