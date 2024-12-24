<?php

namespace App\Twig;

use App\Entity\Coworking\Category;
use App\Entity\Coworking\Event;
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
            new TwigFunction('getEventStatus', [$this, 'getEventStatus']),
            new TwigFunction('getTypePlace', [$this, 'getTypePlace']),
            new TwigFunction('isInArray', [$this, 'isInArray']),
            new TwigFunction('getCategoryBySlug', [$this, 'getCategoryBySlug']),
            new TwigFunction('getCategoryPlace', [$this, 'getCategoryPlace']),
            new TwigFunction('countAvailableByCategory', [$this, 'countAvailableByCategory']),
        ];
    }

    public function getEventStatus(?string $status)
    {
        $labels = Event::getLabels();
        switch ($status) {
            case Event::STATUS_RESERVED:
                return '<span class="badge bg-secondary rounded-pill">'.$labels[Event::STATUS_RESERVED].'</span>';
                break;

            case Event::STATUS_CONFIRMED:
                return '<span class="badge bg-success rounded-pill">'.$labels[Event::STATUS_CONFIRMED].'</span>';
                break;
            
            case Event::STATUS_PAID:
                return '<span class="badge bg-danger rounded-pill">'.$labels[Event::STATUS_PAID].'</span>';
                break;

            case Event::STATUS_CANCELLED:
                return '<span class="badge bg-dark rounded-pill">'.$labels[Event::STATUS_CANCELLED].'</span>';
                break;
            
            default:
                return '<span class="badge bg-info rounded-pill">En attente</span>';
                break;
        }
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
