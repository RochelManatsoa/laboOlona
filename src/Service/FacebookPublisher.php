<?php 
namespace App\Service;

use Psr\Log\LoggerInterface;
use App\Manager\Facebook\FacebookTokenManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FacebookPublisher
{

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private FacebookTokenManager $tokenManager,
    ){}

    public function publish(string $message, ?string $link = null): bool
    {
        $pageId = $this->tokenManager->getPageId();
        $pageToken = $this->tokenManager->getPageAccessToken();

        if (!$pageId || !$pageToken) {
            $this->logger->error("❌ Impossible de récupérer le token ou l'ID de la page.");
            return false;
        }

        $url = sprintf('https://graph.facebook.com/v19.0/%s/feed', $pageId);

        $params = [
            'access_token' => $pageToken,
            'message' => $message,
        ];

        if ($link) {
            $params['link'] = $link;
        }

        try {
            $response = $this->httpClient->request('POST', $url, [
                'body' => $params,
            ]);

            $data = $response->toArray();

            if (isset($data['id'])) {
                $this->logger->info('✅ Publication Facebook réussie : ' . $data['id']);
                return true;
            }

            $this->logger->error('⚠️ Réponse inattendue Facebook : ' . json_encode($data));
            return false;
        } catch (\Throwable $e) {
            $this->logger->error('❌ Erreur publication Facebook : ' . $e->getMessage());
            return false;
        }
    }
}
