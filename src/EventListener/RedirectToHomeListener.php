<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class RedirectToHomeListener
{
    private array $targetHosts;
    private RouterInterface $router;

    public function __construct(array $targetHosts, RouterInterface $router)
    {
        $this->targetHosts = $targetHosts;
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (in_array($request->getHost(), $this->targetHosts, true) && $request->getPathInfo() === '/') {
            $url = $this->router->generate('app_white_label_home');
            $event->setResponse(new RedirectResponse($url));
        }
    }
}
