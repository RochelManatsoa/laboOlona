<?php 

namespace App\OAuth;

use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;

class GoogleDriveClient extends GoogleClient
{
    protected function getOAuth2ProviderOptions(): array
    {
        return array_merge(parent::getOAuth2ProviderOptions(), [
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);
    }
}
