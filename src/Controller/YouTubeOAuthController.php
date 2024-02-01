<?php
// src/Controller/YouTubeOAuthController.php

namespace App\Controller;

use DateTime;
use DateInterval;
use Google_Client;
use App\Entity\Formation\Video;
use App\Service\YouTubeService;
use App\Entity\ModerateurProfile;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Formation\VideoRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Formation\PlaylistRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class YouTubeOAuthController extends AbstractController
{
    private $googleClient;

    public function __construct(
        string $clientId, 
        string $clientSecret, 
        string $redirectUri, 
        private VideoRepository $videoRepository,
        private PlaylistRepository $playlistRepository,
        private EntityManagerInterface $em,
        private UserService $userService,
    )
    {
        $client = new Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setScopes(['https://www.googleapis.com/auth/youtube.readonly']);
        $client->setAccessType('offline');

        $this->googleClient = $client;
    }

    #[Route('/youtube/auth', name: 'youtube_auth')]
    public function auth(Request $request, RequestStack $requestStack)
    {
        if ($request->query->get('code')) {
            $this->googleClient->authenticate($request->query->get('code'));
            $accessToken = $this->googleClient->getAccessToken();
            // Stocker $accessToken dans une session ou une base de données
            $requestStack->getSession()->set('youtube_access_token', $accessToken);
            // Rediriger l'utilisateur vers la page souhaitée après l'authentification
            return new RedirectResponse('/youtube/playlist');
        } else {
            // Rediriger vers Google pour authentification
            $authUrl = $this->googleClient->createAuthUrl();
            return new RedirectResponse($authUrl);
        }
    }


    #[Route('/youtube/playlist', name: 'youtube_playlist')]
    public function showPlaylist(
        YouTubeService $youtubeService, 
        UserService $userService, 
        RequestStack $requestStack
    ): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');

        // Récupérer le jeton d'accès de la session ou de la base de données
        $accessToken = $requestStack->getSession()->get('youtube_access_token');

        // Utiliser le jeton d'accès pour configurer le client Google API
        if ($accessToken) {
            $youtubeService->setAccessToken($accessToken);
        } else {
            // Gérer le cas où l'accès token n'est pas disponible
            // Rediriger vers la route d'authentification par exemple
            return $this->redirectToRoute('youtube_auth');
        }

        $videos = $this->videoRepository->findAll();

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Récupérer les vidéos de la playlist
            $videosChannel = $youtubeService->getPlaylistVideos('PLV5Z9YWBGhPxh4FPyXsU-VLY7PsCOpcpn');
            // dd($videosChannel);
            $videos = [];

            foreach ($videosChannel as $key => $video) {
                $formation = $this->videoRepository->findOneBy(['customId' => $video->id]);
                if(!$formation instanceof Video && $video->snippet->channelId === "UCUYpVkJhLZ_SXSYah6bi9nA"){
                    $formation = new Video();
                }
                $formation->setPublieeLe(new DateTime($video->snippet->publishedAt));
                $formation->setCustomId($video->id);
                $formation->setUrl('https://www.youtube.com/embed/'.$video->id);
                $formation->setTitre($video->snippet->title);
                $formation->setDescription($video->snippet->description);
                $formation->setMiniature($video->snippet->thumbnails->high->url);
                $formation->setNombreVues($video->statistics->viewCount);
                $formation->setNombreLikes($video->statistics->likeCount);
                $formation->setAuteur($video->kind);
                $formation->setDuration($video->contentDetails->duration);
                $formation->setDuree($this->durationToSeconds($video->contentDetails->duration));
                $this->em->persist($formation);
                $videos[] = $formation;
            }
            $this->em->flush();
            $this->addFlash('success', 'Base de données mis à jour.');

        }

        // dd($videos);
        // Rendre une vue avec les vidéos
        return $this->render('dashboard/formation/youtube.html.twig', [
            'videos' => $videos,
        ]);
    }

    private function durationToSeconds(string $duration): int {
        $interval = new DateInterval($duration);
        return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
    }
}
