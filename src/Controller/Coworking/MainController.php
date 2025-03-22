<?php

namespace App\Controller\Coworking;

use App\Manager\MailManager;
use App\Entity\Finance\Devise;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\Coworking\Contract;
use App\Form\Coworking\ContractType;
use App\Entity\BusinessModel\Package;
use App\Entity\Coworking\Reservation;
use App\Entity\Moderateur\ContactForm;
use App\Manager\Marketing\LeadManager;
use App\Form\Moderateur\ContactFormType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Coworking\ReservationFormType;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\Coworking\EventRepository;
use App\Repository\Coworking\PlaceRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\Marketing\SourceRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Coworking\CategoryRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/coworking')]
class MainController extends AbstractController
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private RequestStack $requestStack,
    ){}

    #[Route('/', name: 'app_coworking_main', options: ['sitemap' => true])]
    public function index(
        EventRepository $eventRepository,
        PlaceRepository $placeRepository,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        MailManager $mailManager,
        Security $security,
        Request $request,
        LeadManager $leadManager,
        SourceRepository $sourceRepository
    ): Response
    {
        if($this->isGranted('ROLE_ADMIN')){
            return $this->redirectToRoute('app_main_agenda');
        }
        $sourceContact = $sourceRepository->findOneBy(['slug' => 'formulaire-de-contact-site-olona-talents']);
        $sourceCoworking = $sourceRepository->findOneBy(['slug' => 'formulaire-de-contact-site-coworking']);
        $selectedDate = $request->query->get('date', 'today');
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        $dayAfterTomorrow = (new \DateTime('tomorrow'))->modify('+1 day');

        $params = [];

        $dateMap = [
            'today' => $today,
            'tomorrow' => $tomorrow,
            'dayAfterTomorrow' => $dayAfterTomorrow,
        ];

        $selectedDateTime = $dateMap[$selectedDate] ?? $today;

        $places = $placeRepository->findAll();
        $categories = $categoryRepository->findAll();
        $placesCategories = [];
        foreach ($categories as $category) {
            $placesCategories[$category->getSlug()] = $placeRepository->findPlacesByCategory($category);
        }
        $availablePlaces = $eventRepository->findAvailablePlacesByDate($selectedDateTime);
        $numberOfAvailablePlaces = [];

        foreach ($availablePlaces as $event) {
            $numberOfAvailablePlaces[$event['placeId']] = $numberOfAvailablePlaces[$event['placeId']] ?? 0;
            $numberOfAvailablePlaces[$event['placeId']]++;
        }

        $resa = new Reservation();
        $form = $this->createForm(ReservationFormType::class, $resa);
        $form->handleRequest($request);
        
        $contact = new ContactForm;
        $contact->setCreatedAt(new \DateTime());
        $contactForm = $this->createForm(ContactFormType::class, $contact);
        $contactForm->handleRequest($request);
        if ($contactForm->isSubmitted() && $contactForm->isValid()) {
            $contact = $contactForm->getData();
            $lead = $leadManager->init();
            $lead->setSource($sourceContact);
            $lead->setComment('Formulaire de contact - '.$contact->getMessage());
            $lead->setFullName($contact->getTitre());
            $lead->setEmail($contact->getEmail());
            $lead->setPhone($contact->getNumero());
            $leadManager->save($lead);
            $entityManager->persist($contact);
            $entityManager->flush();
            $mailManager->contactForm($contact);
            $this->addFlash('success', 'Votre message a été bien envoyé. Nous vous repondrons dans le plus bref delais');

            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('coworking/update.html.twig', ['id' => 'contactForm']);
            }
        
            return $this->json([
                'message' => 'Success',
            ], Response::HTTP_OK);
        }

        if($form->isSubmitted() && $form->isValid()){
            $resa = $form->getData();
            $lead = $leadManager->init();
            $lead->setSource($sourceCoworking);
            $lead->setComment('Formulaire de reservation - '.$resa->getDescription());
            $lead->setFullName($resa->getFullName());
            $lead->setEmail($resa->getEmail());
            $lead->setPhone($resa->getPhone());
            $leadManager->save($lead);
            $entityManager->persist($resa);
            $entityManager->flush();
            $mailManager->reservationEnLigne($resa);

            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('coworking/update.html.twig', ['id' => 'reservationForm']);
            }
        
            return $this->json([
                'message' => 'Success',
            ], Response::HTTP_OK);
        }

        $params = [
            'availableToday' => $availablePlaces,
            'availableTodayNumber' => (int)(count($places) - count($availablePlaces)),
            'availableTomorrow' => $eventRepository->findAvailablePlacesByDate($tomorrow),
            'availableTomorrowNumber' => (int)(count($places) - count($eventRepository->findAvailablePlacesByDate($tomorrow))),
            'availableDayAfterTomorrow' => $eventRepository->findAvailablePlacesByDate($dayAfterTomorrow),
            'availableDayAfterTomorrowNumber' => (int)(count($places) - count($eventRepository->findAvailablePlacesByDate($dayAfterTomorrow))),
            'places' => $places,
            'form' => $form->createView(),
            'contactForm' => $contactForm->createView(),
            'date' => (new \DateTime())->format('Y-m-d'),
            'placesCategories' => $placesCategories,
            'categories' => $categories,
        ];

        if($security->isGranted('ROLE_USER')){
            return $this->render('coworking/main/user.html.twig', $params);
        }

        return $this->render('coworking/main/index.html.twig', $params);
    }

    #[Route('/reservations', name: 'app_reservations', methods: ['GET'])]
    public function getReservationsByDate(
        Request $request,
        EventRepository $eventRepository,
        PlaceRepository $placeRepository
    ): Response {
        $date = $request->query->get('date');
        $places = $placeRepository->findAll();
        $placesCategories = [];
        $categories = $this->categoryRepository->findAll();
        foreach ($categories as $category) {
            $placesCategories[$category->getSlug()] = $placeRepository->findPlacesByCategory($category);
        }
        if (!$date) {
            return $this->json(['error' => 'Date not provided'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $selectedDate = new \DateTime($date);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
        }

        $availablePlaces = $eventRepository->findAvailablePlacesByDate($selectedDate);

        $html = $this->renderView('coworking/main/_reservations_list.html.twig', [
            'availablePlaces' => $availablePlaces,
            'date' => $date,
            'dateObject' => new \DateTime($date),
            'places' => $places,
            'categories' => $categories,
            'placesCategories' => $placesCategories,
            'availableOverDateNumber' => (int)(count($places) - count($availablePlaces)),
        ]);

        return $this->json(['html' => $html], Response::HTTP_OK);
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

    #[Route('/contrat/membre-vip', name: 'app_main_vip_contract')]
    public function contract(
        Request $request, 
        MailManager $mailManager, 
        Security $security, 
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var Package $package */
        $package = $entityManager->getRepository(Package::class)->findOneBy([
            'slug' => 'vip-coworking'
        ]);
        $form = $this->createForm(ContractType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contract = $form->getData();
            if($security->getUser()){
                $contract->setUser($security->getUser());
            }
            $contract->setPackage($package);
            $entityManager->persist($contract);
            $entityManager->flush();
            $mailManager->contractVIP($contract);
            $this->addFlash('success', 'Votre contrat a été bien enregistré.');

            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('coworking/update.html.twig', ['id' => 'contractForm']);
            }
        
        
            return $this->json([
                'message' => 'Success',
            ], Response::HTTP_OK);
        }

        return $this->render('coworking/main/membre_vip.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/contrat/flexi', name: 'app_main_flexi_contract')]
    public function flexi(
        Request $request, 
        MailManager $mailManager, 
        Security $security, 
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var Package $package */
        $package = $entityManager->getRepository(Package::class)->findOneBy([
            'slug' => 'pack-flexi'
        ]);
        $form = $this->createForm(ContractType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contract = $form->getData();
            if($security->getUser()){
                $contract->setUser($security->getUser());
            }
            $contract->setPackage($package);
            $entityManager->persist($contract);
            $entityManager->flush();
            $mailManager->contractFLEXI($contract);
            $this->addFlash('success', 'Votre contrat a été bien enregistré.');

            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('coworking/update.html.twig', ['id' => 'contractForm']);
            }
        
        
            return $this->json([
                'message' => 'Success',
            ], Response::HTTP_OK);
        }

        return $this->render('coworking/main/membre_flexi.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/membre-vip/', name: 'app_main_view_vip_contract')]
    public function contractForm(
        Request $request, 
        MailManager $mailManager, 
        Security $security, 
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var Devise $currency */
        $currency = $entityManager->getRepository(Devise::class)->findOneBy([
            'slug' => 'euro'
        ]);
        /** @var Package $package */
        $package = $entityManager->getRepository(Package::class)->findOneBy([
            'slug' => 'vip-coworking'
        ]);
        $form = $this->createForm(ContractType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contract = $form->getData();
            if($security->getUser()){
                $contract->setUser($security->getUser());
            }
            $contract->setPackage($package);
            $entityManager->persist($contract);
            $entityManager->flush();
            $mailManager->contractVIP($contract);
            $this->addFlash('success', 'Votre contrat a été bien enregistré.');

            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('coworking/update.html.twig', ['id' => 'contractFormStep']);
            }
        
        
            return $this->json([
                'message' => 'Success',
            ], Response::HTTP_OK);
        }

        return $this->render('coworking/main/contract_vip.html.twig', [
            'form' => $form->createView(),
            'package' => $package,
            'price' => $this->convertToEuro($package->getPrice(), $currency),
        ]);
    }

    private function convertToEuro(float $price, Devise $currency): float 
    {
        return number_format($price / $currency->getTaux(), 2, '.', '');
    }
}
