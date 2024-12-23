<?php

namespace App\Twig;

use App\Entity\Coworking\Category;
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
            new TwigFunction('isInArray', [$this, 'isInArray']),
            new TwigFunction('getCategoryBySlug', [$this, 'getCategoryBySlug']),
            new TwigFunction('getCategoryPlace', [$this, 'getCategoryPlace']),
            new TwigFunction('countAvailableByCategory', [$this, 'countAvailableByCategory']),
        ];
    }

    public function getTypePlace($id)
    {
        $place = $this->em->getRepository(Place::class)->find($id);
        if(!$place){
            return 'Inconnu';
        }
        if($place->getType() == 'open_space'){
            return 'Open Space';
        }else{
            return 'Cloisonnée';
        }
    }

    public function getCategoryPlace($id)
    {
        $place = $this->em->getRepository(Place::class)->find($id);
        if($place->getCategory() instanceof Category){
            return $place->getCategory()->getName();
        }else{
            return 'Non défini';
        }
    }

    public function countAvailableByCategory(array $availableToday, Category $category)
    {
        $count = 0;
        foreach ($availableToday as $event) {
            if ($event['categoryId'] == $category->getId()) {
                $count++;
            }
        }
        
        return $count;
    }

    public function isInArray(Place $place, array $availableToday)
    {
        foreach ($availableToday as $availableEvent) {
            if ($availableEvent['placeId'] == $place->getId()) {
                return true;
            }
        }
        
        return false;
    }

    public function getCategoryBySlug(string $slug): ?Category
    {
        return $this->em->getRepository(Category::class)->findOneBy(['slug' => $slug]);
    }
}
