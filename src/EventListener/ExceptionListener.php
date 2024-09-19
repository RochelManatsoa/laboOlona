<?php
namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Twig\Environment;

class ExceptionListener
{
    public function __construct(private Environment $twig)
    {}

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        switch ($statusCode) {
            case Response::HTTP_CONFLICT: // 409
                $template = 'bundles/TwigBundle/Exception/error409.html.twig';
                break;
            case Response::HTTP_NOT_FOUND:
                $template = 'bundles/TwigBundle/Exception/error404.html.twig';
                break;
            case Response::HTTP_FORBIDDEN:
                $template = 'bundles/TwigBundle/Exception/error403.html.twig';
                break;
            case Response::HTTP_INTERNAL_SERVER_ERROR:
            default:
                $template = 'bundles/TwigBundle/Exception/error500.html.twig';
            break;
        }

        $html = $this->twig->render($template, [
            'message' => $exception->getMessage(),
        ]);

        $response = new Response($html, $statusCode);

        if ($exception instanceof HttpExceptionInterface) {
            $response->headers->replace($exception->getHeaders());
        }

        $event->setResponse($response);
    }
}
