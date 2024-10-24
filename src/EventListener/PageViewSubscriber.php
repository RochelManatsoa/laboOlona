<?php
namespace App\EventListener;

use App\Service\ActivityLogger;
use App\Service\User\UserService;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class PageViewSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ActivityLogger $activityLogger,
        private UserService $userService,
        private RouterInterface $routerInterface,
    ){}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $user = $this->userService->getCurrentUser();
        $pathInfo = $request->getPathInfo();

        $ignoredPrefixes = [
            '/_components', 
            '/_profiler',   
            '/turbo',   
        ];
    
        if ($user && $request->isXmlHttpRequest()) {
            try {
                $parameters = $this->routerInterface->match($pathInfo);
                if (isset($parameters['_route'])) {
                    // Si le chemin ne commence pas par un préfixe ignoré
                    foreach ($ignoredPrefixes as $prefix) {
                        if (strpos($pathInfo, $prefix) === 0) {
                            return;
                        }
                    }
                    // $this->activityLogger->logPageViewActivity($user, $request->getUri());
                }
            } catch (ResourceNotFoundException | MethodNotAllowedException $e) {
                // Ne rien faire
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }
}