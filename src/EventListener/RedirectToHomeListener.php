<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class RedirectToHomeListener
{
    private string $targetHost;
    private RouterInterface $router;

    public function __construct(string $targetHost, RouterInterface $router)
    {
        $this->targetHost = $targetHost;
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if ($request->getHost() === $this->targetHost && $request->getPathInfo() === '/') {
            $url = $this->router->generate('app_white_label_home');
            $event->setResponse(new RedirectResponse($url));
        }
    }
}
