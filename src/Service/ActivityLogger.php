<?php
namespace App\Service;

use App\Entity\BusinessModel\Credit;
use App\Entity\User;
use App\Entity\Logs\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ){}

    /**
     * Log an activity for a user
     * 
     * @param User $user
     * @param string $activityType
     * @param string|null $details
     * @param int $level
     */
    public function logActivity(
        User $user, 
        string $activityType, 
        ?string $details = null, 
        int $level = ActivityLog::LEVEL_INFO, 
        ?string $pageUrl = null,
        ?int $durationInSeconds = null,
        ?string $deviceType = null,
        ?string $referrer = null,
    ): void
    {
        try {
            $credit = $user->getCredit();
            if(!$credit instanceof Credit){
                $credit = new Credit();
                $credit->setTotal(200);
                $credit->setExpireAt((new \DateTime())->modify('+60 days'));
                $this->entityManager->persist($credit);
                $this->entityManager->persist($user);
                $user->setCredit($credit);
            }
            $log = new ActivityLog();
            $log->setUser($user);
            $log->setActivityType($activityType);
            $log->setTimestamp(new \DateTime());
            $log->setDetails($details);
            $log->setLevel($level);
            $log->setUserCredit($credit->getTotal());
            $log->setIpAddress($this->getIpAddress());
            $log->setUserAgent($this->getUserAgent());
            $log->setPageUrl($pageUrl);
            $log->setDerationInSeconds($durationInSeconds);
            $log->setDeviceType($deviceType);
            $log->setReferrer($referrer);
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        }catch (\Exception $e) {
            return ;
        }
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
     * Log a ai tools view activity
     */
    public function logAiToolsViewActivity(User $user, string $reference): void
    {
        $details = sprintf('Vue outils IA %s', $reference);
        $this->logActivity($user, ActivityLog::ACTIVITY_PAGE_VIEW, $details, ActivityLog::LEVEL_INFO);
    }

    /**
     * Log a page view activity
     */
    public function logPageViewActivity(User $user, string $pageUrl): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $durationInSeconds = $this->calculateDuration(); 
        $referrer = $request->headers->get('referer') ?? '';
        $deviceType = $this->getDeviceType($request->headers->get('User-Agent'));
        $details = sprintf('Page consultée: %s', $pageUrl);
        $this->logActivity(
            $user, 
            ActivityLog::ACTIVITY_PAGE_VIEW, 
            $details, 
            ActivityLog::LEVEL_WARNING,
            $pageUrl,
            $durationInSeconds,
            $deviceType,
            $referrer,
        );
    }

    /**
     * Log a credit spending activity
     */
    public function logCreditSpending(User $user, float $amount, string $context): void
    {
        $details = sprintf('%s crédit dépensé dans le contexte de "%s"', $amount, $context);
        $this->logActivity($user, ActivityLog::ACTIVITY_CREDIT_SPENDING, $details, ActivityLog::LEVEL_INFO);
    }
    
    /**
     * Log a credit purchased activity
     */
    public function logCreditPurchased(User $user, float $amount, string $context): void
    {
        $details = sprintf('Achant crédit: %s via %s', $amount, $context);
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

    private function getDeviceType(string $userAgent): string
    {
        // Simple example function to infer device type via user agent string
        if (stripos($userAgent, 'mobile')) {
            return 'Mobile';
        } elseif (stripos($userAgent, 'tablet')) {
            return 'Tablet';
        }
        return 'Desktop';
    }
    
    private function calculateDuration(): int
    {
        // Placeholder logic: replace with actual duration calculation
        // For example, use JavaScript on the frontend to track user time on page
        // and send the duration back to the backend.
        return 0;
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