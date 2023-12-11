<?php 

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

class ExceptionListener
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        if ($exception instanceof AccessDeniedException) {
            $message = $exception->getMessage();
            $response = new Response($this->twig->render('bundles/TwigBundle/Exception/error403.html.twig', ['message' => $message]));
            $event->setResponse($response);
        }
    }
}
