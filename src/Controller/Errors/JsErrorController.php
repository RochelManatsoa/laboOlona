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
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $userId = null;
        if($user){
            $userId = $user->getId();
        }
        $data = json_decode($request->getContent(), true);
        $errorLog = new ErrorLog();
        $errorLog->setType('javascript');
        $errorLog->setMessage($data['message'] ?? 'No message provided');
        $errorLog->setUrl($data['url'] ?? null);
        $errorLog->setFileName($data['fileName'] ?? null);
        $errorLog->setLineNumber($data['lineNumber'] ?? null);
        // $errorLog->setErrorObj($data['errorObj'] ?? null);
        $errorLog->setUserAgent($data['userAgent'] ?? null);
        $errorLog->setCreatedAt(new \DateTime());
        $errorLog->setUserId($userId);

        $this->errorLogger->logError($errorLog);
        
        return $this->json(['Error logged'], 200, []);
    }
}
