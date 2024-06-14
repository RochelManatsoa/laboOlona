<?php

namespace App\Repository;

use DateTime;
use App\Entity\User;
use App\Entity\Notification;
use App\Data\Profile\StatSearchData;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @param User $user
     * @param array $orderBy
     * @param string|null $statusNot
     * @return Notification[]
     */
    public function findByDestinataireAndStatusNot(User $user, array $orderBy, string $statusNot, ?int $isRead)
    {
        $qb = $this->createQueryBuilder('n')
                ->where('n.destinataire = :destinataire')
                ->setParameter('destinataire', $user);

        if ($statusNot !== null) {
            $qb->andWhere('n.status != :statusNot')
            ->setParameter('statusNot', $statusNot);
        }

        if ($isRead !== null) {
            $qb->andWhere('n.isRead = :isRead')
            ->setParameter('isRead', $isRead);
        }

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy('n.' . $field, $direction);
        }

        return $qb->getQuery()->getResult();
    }
    public function findSearch(StatSearchData $searchData): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.type = :type')
            ->setParameter('type', Notification::TYPE_PROFIL)
            ->orderBy('n.id', 'DESC');

        if (!empty($searchData->start)) {
            $qb = $qb
                ->andWhere('n.dateMessage >= :start')
                ->setParameter('start', $searchData->start->format('Y-m-d') . ' 00:00:00');
        }

        if (!empty($searchData->end)) {
            $qb = $qb
                ->andWhere('n.dateMessage <= :end')
                ->setParameter('end', $searchData->end->format('Y-m-d') . ' 23:59:59');
        }

        if (!empty($searchData->from)) {
            $fromDate = $this->calculateFromDate($searchData->from);
            $qb = $qb
                ->andWhere('n.dateMessage >= :fromDate')
                ->setParameter('fromDate', $fromDate->format('Y-m-d') . ' 00:00:00');
        }

        if (!empty($searchData->user)) {
            $qb = $qb
                ->andWhere('n.expediteur = :user')
                ->setParameter('user', $searchData->user);
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    
    private function calculateFromDate(int $from): DateTime
    {
        $date = new DateTime();
        switch ($from) {
            case 1: // Aujourd'hui
                // Already set to today
                break;
            case 2: // Hier
                $date->modify('-1 day');
                break;
            case 3: // Avant-hier
                $date->modify('-2 days');
                break;
            case 7: // 7 jours
                $date->modify('-7 days');
                break;
            case 30: // 30 jours
                $date->modify('-30 days');
                break;
        }
        return $date;
    }
}
