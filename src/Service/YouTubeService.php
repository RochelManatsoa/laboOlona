<?php

namespace App\Service;

use Google_Client;
use Google_Service_YouTube;

class YouTubeService    
{
    private $youtube;

    public function __construct(string $apiKey)
    {
        $client = new Google_Client();
        $client->setDeveloperKey($apiKey);

        $this->youtube = new Google_Service_YouTube($client);
    }

    public function setAccessToken($accessToken)
    {
        if ($accessToken) {
            $this->youtube->getClient()->setAccessToken($accessToken);
            if ($this->youtube->getClient()->isAccessTokenExpired()) {
                // Si le jeton a expiré, rafraîchissez-le ici
                // Note : cela nécessite un jeton de rafraîchissement
            }
        }
    }

    public function getPlaylistVideos(string $playlistId)
    {
        $videoListParams = [
            'playlistId' => $playlistId,
            'maxResults' => 25
        ];

        $playlistItems = $this->youtube->playlistItems->listPlaylistItems('snippet,contentDetails', $videoListParams);
        $videos = [];

        foreach ($playlistItems['items'] as $item) {
            $videoId = $item['contentDetails']['videoId'];
            $videoResponse = $this->youtube->videos->listVideos('snippet,contentDetails,statistics', ['id' => $videoId]);

            if (!empty($videoResponse['items'])) {
                $video = $videoResponse['items'][0];
                $publishedAt = new \DateTime($video['snippet']['publishedAt']);
                $now = new \DateTime();
                $interval = $publishedAt->diff($now);

                $video['timeDiff'] = $this->formatTimeDiff($interval);
                $videos[] = $video;
            }
        }
        return $videos;
    }

    
    public function getChannelInfo(string $channelId)
    {
        $channelParams = [
            'id' => $channelId,
            'part' => 'snippet,contentDetails,statistics',
        ];

        $channelResponse = $this->youtube->channels->listChannels('snippet,contentDetails,statistics', $channelParams);

        if (empty($channelResponse['items'])) {
            throw new \Exception('Channel not found');
        }

        // Retourne les informations de la chaîne
        // dd($channelResponse['items'][0]);
        return $channelResponse['items'][0];
    }

    private function formatTimeDiff(\DateInterval $interval) {
        if ($interval->y > 0) {
            return "Il y a " . $interval->y . " années";
        } elseif ($interval->m > 0) {
            return "Il y a " . $interval->m . " mois";
        } elseif ($interval->d > 0) {
            return "Il y a " . $interval->d . " jours";
        } else {
            return "Aujourd'hui";
        }
    }

    public function logout()
    {
        $client = $this->youtube->getClient();
        $accessToken = $client->getAccessToken();

        if ($accessToken) {
            // Révoquer le token d'accès
            $this->revokeAccessToken($accessToken);

            // Effacer le token d'accès du client
            $client->revokeToken();
        }

        // Autres opérations de déconnexion, comme la suppression des données de session, si nécessaire
    }

    private function revokeAccessToken($accessToken)
    {
        $token = is_array($accessToken) ? $accessToken['youtube_access_token'] : $accessToken;
        file_get_contents('https://accounts.google.com/o/oauth2/revoke?token=' . $token);
    }

}
