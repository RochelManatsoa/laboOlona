<?php

namespace App\Repository;

use DateTime;
use App\Entity\User;
use App\Data\UserData;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
    
    public function findUsersRegisteredToday(): array
    {
        $qb = $this->createQueryBuilder('u');

        // Détermine la date de début (aujourd'hui à 00h00)
        // $startDate = new \DateTime('today midnight');
        
        // Détermine la date de fin (maintenant)
        $endDate = new \DateTime('now');
        $startDate = clone $endDate;
        $startDate->modify('-28 days');

        $qb->where('u.dateInscription >= :startDate')
           ->andWhere('u.dateInscription <= :endDate')
           ->setParameter('startDate', $startDate)
           ->setParameter('endDate', $endDate);

        return $qb->getQuery()->getResult();
    }

    public function countUsersByType(): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.type AS userType', 'COUNT(u.id) AS userCount')
            ->groupBy('u.type');

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function countUsersRegisteredTodayByType(): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.type AS userType', 'COUNT(u.id) AS userCount')
            ->where('u.dateInscription >= :startOfDay')
            ->setParameter('startOfDay', new \DateTime('today'))
            ->groupBy('u.type');

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function countUsersRegisteredToday(): int
    {
        // Définir la date de début à aujourd'hui à 00h00
        $startOfDay = new \DateTime('today');
        
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id) AS totalUsers')
            ->where('u.dateInscription >= :startOfDay')
            ->setParameter('startOfDay', $startOfDay);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
    public function countAllUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findExpiredBoostVisibilitiesByType(string $type): array
    {
        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.boostVisibilities', 'bv')
            ->where('bv.type = :type')
            ->andWhere('bv.endDate < :now')
            ->setParameter('type', $type)
            ->setParameter('now', new \DateTime())
            ->getQuery();

        return $qb->getResult();
    }

    public function findExpiredBoostVisibilities(): array
    {
        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.boostVisibilities', 'bv')
            ->where('bv.endDate < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery();

        return $qb->getResult();
    }

    public function paginateUsers(array $data): PaginationInterface
    {
        if ($data['days'] > 1) {
            return $this->gatUsersLogedInSince($data['days'], $data['page']);
        }
        if ($data['startDate'] === "" && $data['endDate'] === "") {
            return $this->gatUsersLogedInToday($data['page']);
        }
        $startDate = new \DateTime($data['startDate']);
        $endDate = new \DateTime($data['endDate']);

        $qb = $this->createQueryBuilder('u')
            ->where('u.lastLogin >= :startDate')
            ->andWhere('u.lastLogin <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('u.lastLogin', 'DESC')
            ->getQuery();

        return $this->paginator->paginate(
            $qb,
            $data['page'],
            20,
            [
                'distinct' => true,
                'shortFieldAllowList' => ['u.id', 'u.type', 'u.dateInscription', 'u.lastLogin'],
            ]
        );
    }

    public function gatUsersLogedInToday(int $page = 1): PaginationInterface
    {
        // Définir la date de début à aujourd'hui à 00h00
        $startOfDay = new \DateTime('today');
        
        $qb = $this->createQueryBuilder('u')
            ->where('u.lastLogin >= :startOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->orderBy('u.lastLogin', 'DESC');

        return $this->paginator->paginate(
            $qb,
            $page,
            20,
            [
                'distinct' => true,
                'shortFieldAllowList' => ['u.id', 'u.type', 'u.dateInscription', 'u.lastLogin'],
            ]
        );
    }

    public function gatUsersLogedInSince(int $days, int $page = 1): PaginationInterface
    {
        // Calculer la date correspondant au nombre de jours passés
        $dateSince = (new \DateTime())->modify("-$days days");

        $qb = $this->createQueryBuilder('u')
            ->where('u.lastLogin >= :dateSince')
            ->setParameter('dateSince', $dateSince)
            ->orderBy('u.lastLogin', 'DESC');


        return $this->paginator->paginate(
            $qb,
            $page,
            20,
            [
                'distinct' => true,
                'shortFieldAllowList' => ['u.id', 'u.type', 'u.dateInscription', 'u.lastLogin'],
            ]
        );
    }
    
    public function paginateRecipes($page, ?int $userId): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('u')->select('u');
        $queryBuilder->addOrderBy('u.id', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            20,
            [
                'distinct' => false,
                'shortFieldAllowList' => ['id', 'email', 'type', 'nom'],
            ]
        );
    }
}
