<?php
namespace App\EventListener;

use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExceptionListener
{
    public function __construct(
        private Environment $twig,
        private RequestStack $requestStack,
        private RouterInterface $router,
    )
    {}

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();
        $currentUri = $request->getUri(); // Récupérer l'URL demandée

        if ($exception instanceof AccessDeniedException) {
            $session->set('_security.main.target_path', $currentUri);

            if (!$request->getUser()) { 
                $response = new RedirectResponse($this->router->generate('app_login'));
            } else {
                $response = new RedirectResponse($currentUri);
            }

            $event->setResponse($response);
            return; 
        }

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        $template = match ($statusCode) {
            Response::HTTP_CONFLICT => 'bundles/TwigBundle/Exception/error409.html.twig',
            Response::HTTP_NOT_FOUND => 'bundles/TwigBundle/Exception/error404.html.twig',
            Response::HTTP_FORBIDDEN => 'bundles/TwigBundle/Exception/error403.html.twig',
            default => 'bundles/TwigBundle/Exception/error500.html.twig',
        };

        $html = $this->twig->render($template, ['message' => $exception->getMessage()]);
        $response = new Response($html, $statusCode);

        if ($exception instanceof HttpExceptionInterface) {
            $response->headers->replace($exception->getHeaders());
        }

        $event->setResponse($response);
    }
}
