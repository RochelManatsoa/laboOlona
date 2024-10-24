<?php
namespace App\Service;

use App\Entity\User;
use App\Entity\Logs\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogger
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Log an activity for a user
     * 
     * @param User $user
     * @param string $activityType
     * @param string|null $details
     * @param int $level
     */
    public function logActivity(User $user, string $activityType, ?string $details = null, int $level = ActivityLog::LEVEL_INFO): void
    {
        $log = new ActivityLog();
        $log->setUser($user);
        $log->setActivityType($activityType);
        $log->setTimestamp(new \DateTime());
        $log->setDetails($details);
        $log->setLevel($level);
        $log->setUserCredit($user->getCredit()->getTotal());
        $log->setIpAddress($this->getIpAddress());
        $log->setUserAgent($this->getUserAgent());
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * Log a search activity
     */
    public function logSearchActivity(User $user, string $query, string $type = "Olona Talents"): void
    {
        $details = sprintf('Requête de recherche: "%s" dans "%s" ', $query, $this->getType($type));
        $this->logActivity($user, ActivityLog::ACTIVITY_SEARCH, $details, ActivityLog::LEVEL_INFO);
    }

    /**
     * Log a profil view activity
     */
    public function logProfileViewActivity(User $user, string $reference): void
    {
        $details = sprintf('Vue profil %s', $reference);
        $this->logActivity($user, ActivityLog::ACTIVITY_PAGE_VIEW, $details, ActivityLog::LEVEL_INFO);
    }

    /**
     * Log a prestation view activity
     */
    public function logPrestationViewActivity(User $user, string $reference): void
    {
        $details = sprintf('Vue prestation %s', $reference);
        $this->logActivity($user, ActivityLog::ACTIVITY_PAGE_VIEW, $details, ActivityLog::LEVEL_INFO);
    }

    /**
     * Log a joblisting view activity
     */
    public function logJobLisitinViewActivity(User $user, string $reference): void
    {
        $details = sprintf('Vue offre d\'emploi %s', $reference);
        $this->logActivity($user, ActivityLog::ACTIVITY_PAGE_VIEW, $details, ActivityLog::LEVEL_INFO);
    }

    /**
     * Log a page view activity
     */
    public function logPageViewActivity(User $user, string $pageUrl): void
    {
        $details = sprintf('Page consultée: %s', $pageUrl);
        $this->logActivity($user, ActivityLog::ACTIVITY_PAGE_VIEW, $details, ActivityLog::LEVEL_WARNING);
    }

    /**
     * Log a credit spending activity
     */
    public function logCreditSpending(User $user, float $amount, string $context): void
    {
        $details = sprintf('Crédit dépensé: % dans le contexte de "%s"', $amount, $context);
        $this->logActivity($user, ActivityLog::ACTIVITY_CREDIT_SPENDING, $details, ActivityLog::LEVEL_INFO);
    }
    
    /**
     * Log a credit purchased activity
     */
    public function logCreditPurchased(User $user, float $amount, string $context): void
    {
        $details = sprintf('Achant crédit: %.2f via %s', $amount, $context);
        $this->logActivity($user, ActivityLog::ACTIVITY_CREDIT_SPENDING, $details, ActivityLog::LEVEL_INFO);
    }
    /**
     * Retrieve user's IP address
     *
     * @return string|null
     */
    private function getIpAddress(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Retrieve the user agent string
     *
     * @return string|null
     */
    private function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    private function getType($type): string
    {
        $searchType = "";
        switch ($type) {
            case 'candidates':
                $searchType = "profils";
                break;

            case 'joblistings':
                $searchType = "offre d'emploi";
                break;
                
            case 'prestations':
                $searchType = "prestations";
                break;
            
            default:
                $searchType = "Olona Talents";
                break;
        }

        return $searchType;
    }
}