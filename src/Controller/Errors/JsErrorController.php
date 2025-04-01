<?php

namespace App\Controller\Errors;

use App\Entity\User;
use App\Service\ErrorLogger;
use App\Entity\Errors\ErrorLog;
use App\Service\User\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class JsErrorController extends AbstractController
{
    public function __construct(
        private ErrorLogger $errorLogger,
        private UserService $userService,
    )
    {}

    #[Route('/errors/js/error', name: 'app_errors_js_error')]
    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = $this->userService->getCurrentUser();

        $data = json_decode($request->getContent(), true);
        $longueurMax = 255;

        $truncate = fn($value) => mb_substr($value ?? '', 0, $longueurMax);

        $errorLog = (new ErrorLog())
            ->setType('javascript')
            ->setMessage($truncate($data['message']) ?: 'No message provided')
            ->setErrorMessage($data['message'] ?? 'No message provided')
            ->setUrl($truncate($data['url']))
            ->setFileName($truncate($data['fileName']))
            ->setLineNumber($truncate($data['lineNumber']))
            ->setErrorObject($data['errorObj'] ?? null)
            ->setUserAgent($truncate($data['userAgent']))
            ->setCreatedAt(new \DateTime())
            ->setUserId($user?->getId());

        $this->errorLogger->logError($errorLog);

        return $this->json(['Error logged']);
    }
}
