<?php

namespace App\Repository\Blog;

use App\Entity\Blog\Post;
use App\Entity\Blog\Comment;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Comment::class);
    }

   /**
    * @return Comment[] Returns an array of Comment objects
    */
   public function findValidComments(Post $post): array
   {
       return $this->createQueryBuilder('c')
           ->andWhere('c.post = :post')
           ->setParameter('post', $post)
           ->andWhere('c.status = :status')
           ->setParameter('status', Comment::STATUS_VALIDATED)
           ->orderBy('c.createdAt', 'DESC')
           ->getQuery()
           ->getResult()
       ;
   }

   public function paginateComments($page): PaginationInterface
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

//    public function findOneBySomeField($value): ?Comment
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
