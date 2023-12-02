<?php

namespace App\Controller\Dashboard;

use App\Entity\Formation\Video;
use App\Service\YouTubeService;
use App\Entity\ModerateurProfile;
use App\Form\Formation\VideoType;
use App\Manager\FormationManager;
use App\Service\User\UserService;
use App\Entity\Formation\Playlist;
use App\Form\Formation\PlaylistType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Formation\VideoRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Formation\PlaylistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/formation')]
class FormationController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private PlaylistRepository $playlistRepository,
        private VideoRepository $videoRepository,
        private FormationManager $formationManager,
        private EntityManagerInterface $em,
    ) {
    }

    private function checkModerateur()
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $moderateur = $user->getModerateurProfile();
        if (!$moderateur instanceof ModerateurProfile){ 
            return $this->redirectToRoute('app_connect');
        }

        return null;
    }

    #[Route('/', name: 'app_dashboard_formation')]
    public function index(): Response
    {
        return $this->render('dashboard/formation/index.html.twig', [
            'playlists' => $this->playlistRepository->findAll(),
            'videos' => $this->videoRepository->findAll(),
        ]);
    }

    #[Route('/videos', name: 'app_dashboard_formation_videos')]
    public function videos(): Response
    {
        return $this->render('dashboard/formation/videos.html.twig', [
            'playlists' => $this->playlistRepository->findAll(),
            'videos' => $this->videoRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_dashboard_formation_new')]
    public function new(Request $request): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Initialiser une instance de Video */
        $video = $this->formationManager->init();
        $form = $this->createForm(VideoType::class, $video);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Sauvegarder Video */
            $video = $this->formationManager->save($form->getData());
            $this->addFlash('success', 'Vidéo sauvegardée');

            return $this->redirectToRoute('app_dashboard_formation', []);
        }

        return $this->render('dashboard/formation/new_edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/edit/{id}', name: 'app_dashboard_formation_edit')]
    public function edit(Request $request, Video $video): Response
    {
        $form = $this->createForm(VideoType::class, $video);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Sauvegarder Video */
            $video = $this->formationManager->save($form->getData());
            $this->addFlash('success', 'Vidéo sauvegardée');

            return $this->redirectToRoute('app_dashboard_formation', []);
        }

        return $this->render('dashboard/formation/new_edit.html.twig', [
            'video' => $video,
            'form' => $form->createView()
        ]);
    }

    #[Route('/view/{id}', name: 'app_dashboard_formation_view')]
    public function view(Request $request, Video $video): Response
    {

        return $this->render('dashboard/formation/view.html.twig', [
            'video' => $video,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_dashboard_formation_delete')]
    public function delete(Request $request, Video $video): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        if ($this->isCsrfTokenValid('delete' . $video->getId(), $request->request->get('_token'))) {
            $this->em->remove($video);
            $this->em->flush();
        }

        return $this->redirectToRoute('app_dashboard_formation');
    }

    #[Route('/playlist/new', name: 'app_dashboard_formation_playlist_new')]
    public function newPlaylist(Request $request): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Initialiser une instance de Playlist */
        $playlist = $this->formationManager->initPlaylist();
        $form = $this->createForm(PlaylistType::class, $playlist);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Sauvegarder Playlist */
            $playlist = $this->formationManager->savePlaylist($form->getData());
            $this->addFlash('success', 'Playlist sauvegardé');

            return $this->redirectToRoute('app_dashboard_formation', []);
        }

        return $this->render('dashboard/formation/new_edit_playlist.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/playlist/edit/{id}', name: 'app_dashboard_formation_playlist_edit')]
    public function editPlaylist(Request $request, Playlist $playlist): Response
    {
        $form = $this->createForm(PlaylistType::class, $playlist);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Sauvegarder Playlist */
            $playlist = $form->getData();
            foreach ($playlist->getVideos() as $video) {
                $video->setPlaylist($playlist);
                $this->em->persist($video); // Persiste chaque vidéo
            }

            $this->em->persist($playlist);
            $this->em->flush();
            $this->addFlash('success', 'Playlist sauvegardé');

            return $this->redirectToRoute('app_dashboard_formation', []);
        }

        return $this->render('dashboard/formation/new_edit_playlist.html.twig', [
            'playlist' => $playlist,
            'form' => $form->createView()
        ]);
    }

    #[Route('/playlist/view/{id}', name: 'app_dashboard_formation_playlist_view')]
    public function viewPlaylist(Request $request, Playlist $playlist): Response
    {
        return $this->render('dashboard/formation/view_playlist.html.twig', [
            'playlist' => $playlist,
        ]);
    }

    #[Route('/playlist/delete/{id}', name: 'app_dashboard_formation_playlist_delete')]
    public function deletePlaylist(Request $request, Playlist $playlist): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        if ($this->isCsrfTokenValid('delete' . $playlist->getId(), $request->request->get('_token'))) {
            $this->em->remove($playlist);
            $this->em->flush();
        }

        return $this->redirectToRoute('app_dashboard_formation');
    }
}
