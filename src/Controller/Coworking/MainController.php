<?php

namespace App\Controller\Coworking;

use App\Repository\Coworking\EventRepository;
use App\Repository\Coworking\PlaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/coworking')]
class MainController extends AbstractController
{
    #[Route('/', name: 'app_coworking_main')]
    public function index(
        EventRepository $eventRepository,
        PlaceRepository $placeRepository,
        Request $request
    ): Response
    {
        $selectedDate = $request->query->get('date', 'today');
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        $dayAfterTomorrow = (new \DateTime('tomorrow'))->modify('+1 day');

        $dateMap = [
            'today' => $today,
            'tomorrow' => $tomorrow,
            'dayAfterTomorrow' => $dayAfterTomorrow,
        ];

        $selectedDateTime = $dateMap[$selectedDate] ?? $today;

        $places = $placeRepository->findAll();
        $availablePlaces = $eventRepository->findAvailablePlacesByDate($selectedDateTime);
        $numberOfAvailablePlaces = [];

        foreach ($availablePlaces as $event) {
            $numberOfAvailablePlaces[$event['placeId']] = $numberOfAvailablePlaces[$event['placeId']] ?? 0;
            $numberOfAvailablePlaces[$event['placeId']]++;
        }

        return $this->render('coworking/main/index.html.twig', [
            'availableToday' => $availablePlaces,
            'availableTodayNumber' => (int)(count($places) - count($numberOfAvailablePlaces)),
            'availableTomorrow' => $eventRepository->findAvailablePlacesByDate($tomorrow),
            'availableTomorrowNumber' => (int)(count($places) - count($numberOfAvailablePlaces)),
            'availableDayAfterTomorrow' => $eventRepository->findAvailablePlacesByDate($dayAfterTomorrow),
            'availableDayAfterTomorrowNumber' => (int)(count($places) - count($numberOfAvailablePlaces)),
            'places' => $places,
        ]);
    }

    #[Route('/agenda', name: 'app_main_agenda')]
    public function agenda(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAll();
        $reservations = [];
        foreach ($events as $event) {
            $places = [];
            foreach ($event->getPlaces() as $place) {
                $places[] = [
                    'id' => $place->getId(),
                    'name' => $place->getName(),
                ];
            }
            $reservations[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'user' => $event->getUser() ? $event->getUser()->getEmail() : 'Non défini',
                'places' => $places,
                'start' => $event->getStartEvent()->format('Y-m-d H:i:s'),
                'end' => $event->getEndEvent()->format('Y-m-d H:i:s'),
                'allDay' => $event->isAllDay(),
                'backgroundColor' => $event->getBackgroundColor(),
                'borderColor' => $event->getBorderColor(),
                'textColor' => $event->getTextColor(),
            ];
        }
        return $this->render('coworking/main/agenda.html.twig', [
            'data' => json_encode($reservations),
        ]);
    }
}
