<?php

namespace App\Twig;

use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Entity\Coworking\Place;
use Twig\Extension\AbstractExtension;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Moderateur\AssignationRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class CoworkingExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private Security $security,
        private EntityManagerInterface $em,
        private AssignationRepository $assignationRepository,
        private JobListingRepository $jobListingRepository,
        private NotificationRepository $notificationRepository,
    ) {}

    public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getTypePlace', [$this, 'getTypePlace']),
        ];
    }

    public function getTypePlace($id)
    {
        $place = $this->em->getRepository(Place::class)->find($id);
        if(!$place){
            return 'Inconnu';
        }
        if($place->getType() == 'open-space'){
            return 'Open Space';
        }else{
            return 'Cloisonnée';
        }
    }
}
