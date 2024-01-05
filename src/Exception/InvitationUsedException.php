<?php

namespace App\Exception; 

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class InvitationUsedException extends RuntimeException implements HttpExceptionInterface
{
    public function getStatusCode():int
    {
        return Response::HTTP_CONFLICT; // 409
    }

    public function getHeaders():array
    {
        return [];
    }
}
