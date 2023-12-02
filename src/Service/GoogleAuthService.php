<?php
// src/Service/GoogleAuthService.php

namespace App\Service;

use Google_Client;
use Google_Service_YouTube;
use Symfony\Component\HttpFoundation\RequestStack;

class GoogleAuthService
{
    private $client;
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;

        $client = new Google_Client();
        $client->setClientId('YOUR_CLIENT_ID');
        $client->setClientSecret('YOUR_CLIENT_SECRET');
        $client->setRedirectUri('YOUR_REDIRECT_URI');
        $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $this->client = $client;
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function authenticate($code)
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
        $this->client->setAccessToken($accessToken);

        // Store the token in your application for later use
        // ...

        return $this->client;
    }

    public function getClient()
    {
        return $this->client;
    }
}
