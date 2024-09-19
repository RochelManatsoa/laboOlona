<?php

namespace App\Manager;

use App\Entity\Formation\Playlist;
use DateTime;
use Twig\Environment as Twig;
use App\Entity\Formation\Video;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;

class FormationManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private SluggerInterface $sluggerInterface,
        private Security $security
    ){        
    }

    public function init(): Video
    {
        $video = new Video();
        $video->setStatus('PENDING');

        return $video;
    }

    public function initPlaylist(): Playlist
    {
        $playlist = new Playlist();
        $playlist->setStatus('PENDING');

        return $playlist;
    }

    public function save(Video $video)
    {
        $video->setPublieeLe(new DateTime());
        $this->em->persist($video);
        $this->em->flush();
    }

    public function savePlaylist(Playlist $playlist)
    {
        $playlist->setCreatedAt(new DateTime());
        $playlist->setSlug($this->sluggerInterface->slug(strtolower($playlist->getTitre())));
        foreach ($playlist->getVideos() as $video) {
            $playlist->addVideo($video); // Supposant que vous avez une méthode addVideo
            $this->em->persist($video);
        }
        $this->em->persist($playlist);
        $this->em->flush();
    }

    // public function findAllAITools(): array
    // {
    //     $tools = $this->affiliateToolRepository->findBy(
    //         ['type' => 'publish'],
    //         [ 'id' => 'DESC']
    //     );

    //     return $tools;
    // }

}