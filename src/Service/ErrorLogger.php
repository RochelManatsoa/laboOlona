<?php

namespace App\Service;

use App\Entity\User;
use App\Manager\MailManager;
use App\Entity\Errors\ErrorLog;
use App\Entity\Logs\ActivityLog;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ErrorLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private ManagerRegistry $managerRegistry,
        private UserService $userService,
        private ActivityLogger $activityLogger,
        private MailManager $mailManager,
    )
    {}

    public function logError(ErrorLog $errorLog)
    {
        if (!$this->em->isOpen()) {
            $this->em = $this->managerRegistry->resetManager(); // Réinitialise l'EntityManager si nécessaire
        }

        $this->em->persist($errorLog);
        $this->em->flush();
    }
    
    public function onKernelException(ExceptionEvent $event): void
    {
        /** @var User|null $user */
        $user = $this->userService->getCurrentUser();
        $exception = $event->getThrowable();
        $request = $this->requestStack->getCurrentRequest();

        $url = $request?->getUri() ?? 'N/A';
        $userAgent = $request?->headers->get('User-Agent') ?? 'N/A';
        $longueurMax = 255;

        $truncate = fn($value) => mb_substr((string) $value, 0, $longueurMax);

        if ($user) {
            $this->activityLogger->logActivity(
                $user,
                ActivityLog::ACTIVITY_ERROR,
                $exception->getMessage(),
                ActivityLog::LEVEL_CRITICAL
            );

            $this->mailManager->errorAlertUser($user, $url, $exception);
        }

        $errorLog = (new ErrorLog())
            ->setType('php')
            ->setMessage($truncate($exception->getMessage()))
            ->setErrorMessage($exception->getMessage())
            ->setUrl($truncate($url))
            ->setFileName($truncate($exception->getFile()))
            ->setLineNumber($truncate($exception->getLine()))
            ->setErrorObject($exception->getTraceAsString())
            ->setUserAgent($truncate($userAgent))
            ->setUserId($user?->getId())
            ->setCreatedAt(new \DateTime());

        $this->logError($errorLog);
    }
}
