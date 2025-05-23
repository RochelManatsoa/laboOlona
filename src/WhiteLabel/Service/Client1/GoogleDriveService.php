<?php

namespace App\WhiteLabel\Service\Client1;

use Google_Client;
use Google\Service\Drive as Google_Service_Drive;

class GoogleDriveService
{
    private Google_Client $client;
    private Google_Service_Drive $drive;

    public function __construct(string $credentialsPath, string $tokenPath)
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig($credentialsPath);

        // ✅ SCOPE uniquement pour Drive
        $this->client->setScopes([Google_Service_Drive::DRIVE_READONLY]);

        $this->client->setAccessType('offline');      // important pour refresh_token
        $this->client->setPrompt('consent');          // force la récupération du refresh_token
        $this->client->setRedirectUri('https://client1.olona-talents.local.com:8000/connect/google-drive/check');

        // ✅ Vérifie si token existe
        if (!file_exists($tokenPath)) {
            throw new \RuntimeException('Fichier token.json introuvable. Veuillez connecter Google Drive.');
        }

        // ✅ Lecture du token
        $token = json_decode(file_get_contents($tokenPath), true);
        $this->client->setAccessToken($token);

        // 🔁 Si le token est expiré, mais qu'on a un refresh_token : on rafraîchit
        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = $this->client->getRefreshToken();

            if (!$refreshToken) {
                throw new \RuntimeException('Token expiré et aucun refresh_token disponible. Veuillez vous reconnecter à Google Drive.');
            }

            $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
            $newToken['refresh_token'] = $refreshToken; // 🔁 parfois Google ne le renvoie pas, on le réinjecte
            file_put_contents($tokenPath, json_encode($newToken));
            $this->client->setAccessToken($newToken);
        }

        // ✅ Instanciation du service Google Drive
        $this->drive = new Google_Service_Drive($this->client);
    }

    public function listFiles(): \Google\Service\Drive\FileList
    {
        return $this->drive->files->listFiles(['pageSize' => 10]);
    }

    public function downloadFile(string $fileId, string $destination): void
    {
        $response = $this->drive->files->get($fileId, ['alt' => 'media']);
        dd($response);
        file_put_contents($destination, $response->getBody()->getContents());
    }

    public function getClient(): Google_Client
    {
        return $this->client;
    }
}
