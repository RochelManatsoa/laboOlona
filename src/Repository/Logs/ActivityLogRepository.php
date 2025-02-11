<?php

namespace App\Repository\Logs;

use App\Entity\Logs\ActivityLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 *
 * @method ActivityLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityLog[]    findAll()
 * @method ActivityLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry, 
        private PaginatorInterface $paginator,
        private UrlGeneratorInterface $urlGenerator,
    )
    {
        parent::__construct($registry, ActivityLog::class);
    }

   /**
    * @return ActivityLog[] Returns an array of ActivityLog objects
    */
   public function findUserLogs($user): array
   {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.level < :level')
            ->setParameter('user', $user)
            ->setParameter('level', ActivityLog::LEVEL_WARNING)
            ->orderBy('a.timestamp', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
        ;
   }
   
   public function paginateActivities(int $page, User $user): PaginationInterface
   {
        return $this->paginator->paginate(
            $this->createQueryBuilder('a')
                ->andWhere('a.user = :user')
                ->setParameter('user', $user)
                ->orderBy('a.timestamp', 'DESC'),
            $page,
            20,
            [
                'distinct' => true,
                'shortFieldAllowList' => ['a.activityType', 'a.timestamp', 'a.level'],
            ]
        );
    }

    public function findAllPageVieuw()
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.activityType = :type')
            ->andWhere('a.derationInSeconds IS NULL')
            ->setParameter('type', ActivityLog::ACTIVITY_PAGE_VIEW)
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();
    }

    public function getUniqueUserPageViewsByDate(array $array = []): array
    {
        $qb = $this->createQueryBuilder('a')
        ->select('SUBSTRING(a.timestamp, 1, 10) as date, COUNT(DISTINCT a.user) as uniqueUsers')
        ->where('a.activityType = :pageView')
        ->setParameter('pageView', ActivityLog::ACTIVITY_PAGE_VIEW);
        if(!empty($array['route'])) {
            $qb
            ->andWhere('a.pageUrl = :url')
            ->setParameter('url', $this->urlGenerator->generate($array['route']));
        }
        $qb
        ->groupBy('date')
        ->orderBy('date', 'ASC');

        return $qb->getQuery()->getResult();
    }

    
    public function findLikeLogs(string $route): array
    {
        $qb = $this->createQueryBuilder('al')
            ->select('al.pageUrl, COUNT(al.pageUrl) as pageCount')
            ->where('al.pageUrl LIKE :route')
            ->setParameter('route', '%' . substr($this->urlGenerator->generate($route), 0, -1) . '%')
            ->groupBy('al.pageUrl')
            ->orderBy('pageCount', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
