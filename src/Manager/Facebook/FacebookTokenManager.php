<?php 

namespace App\Manager\Facebook;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FacebookTokenManager
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $userToken,
        private string $pageName
    ) {}

    public function getPageAccessToken(): ?string
    {
        $url = 'https://graph.facebook.com/v19.0/me/accounts?access_token=' . $this->userToken;

        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();

            foreach ($data['data'] as $page) {
                if ($page['name'] === $this->pageName) {
                    return $page['access_token'];
                }
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getPageId(): ?string
    {
        $url = 'https://graph.facebook.com/v19.0/me/accounts?access_token=' . $this->userToken;

        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();

            foreach ($data['data'] as $page) {
                if ($page['name'] === $this->pageName) {
                    return $page['id'];
                }
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
