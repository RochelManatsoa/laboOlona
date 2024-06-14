<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Errors\ErrorLog;
use App\Service\User\UserService;
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
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $userId = null;
        if($user){
            $userId = $user->getId();
        }
        $exception = $event->getThrowable();
        $request = $this->requestStack->getCurrentRequest();

        $url = $request ? $request->getUri() : 'N/A';
        $userAgent = $request ? $request->headers->get('User-Agent') : 'N/A';
        $errorLog = new ErrorLog();
        $longueurMax = 255; // ou toute autre limite appropriée pour votre colonne
        $messageTronque = mb_substr($exception->getMessage(), 0, $longueurMax);
        $errorLog->setMessage($messageTronque)
            ->setType('php') 
            ->setUrl($url) 
            ->setFileName($exception->getFile()) 
            ->setLineNumber($exception->getLine()) 
            ->setErrorObject($exception->getTraceAsString())
            ->setErrorMessage($exception->getMessage())
            ->setUserAgent($userAgent) 
            ->setUserId($userId) 
            ->setCreatedAt(new \DateTime()); 

        $this->logError($errorLog);
    }
}
