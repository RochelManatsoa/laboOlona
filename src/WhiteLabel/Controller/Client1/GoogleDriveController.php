<?php

namespace App\WhiteLabel\Controller\Client1;

use Symfony\Component\HttpFoundation\Request;
use App\WhiteLabel\Service\Client1\GoogleDriveService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GoogleDriveController extends AbstractController
{
    public function __construct(
        private ParameterBagInterface $params
    ) {}

    #[Route('/connect/google-drive', name: 'connect_google_drive')]
    public function connectDrive(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google_drive')
            ->redirect([
                'scope' => 'https://www.googleapis.com/auth/drive.readonly',
            ]);
    }

    #[Route('/connect/google-drive/check', name: 'google_drive_callback')]
    public function callback(ClientRegistry $clientRegistry): RedirectResponse
    {
        $client = $clientRegistry->getClient('google_drive');
        $accessToken = $client->getAccessToken();

        // ✅ Enregistre le token dans un fichier (token.json)
        $tokenPath = $this->params->get('google_drive.token_path');
        file_put_contents($tokenPath, json_encode($accessToken->jsonSerialize()));

        $this->addFlash('success', 'Connexion à Google Drive réussie.');

        return $this->redirectToRoute('google_drive_list');
    }

    #[Route('/drive/list', name: 'google_drive_list')]
    public function list(GoogleDriveService $driveService): Response
    {
        $files = $driveService->listFiles();
        dd($files); // ✅ Afficher les fichiers récupérés

        return $this->render('drive/list.html.twig', [
            'files' => $files,
        ]);
    }
}
