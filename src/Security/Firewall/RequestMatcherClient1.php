<?php

namespace App\Security\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class RequestMatcherClient1 implements RequestMatcherInterface
{
    public function matches(Request $request): bool
    { 
        dd($request->getHost()); 
        return str_contains($request->getHost(), 'client1.');
    }
}
