<?php

namespace App\Controller\Coworking;

use App\Entity\User;
use App\Service\Cart;
use App\Entity\Coworking\Event;
use App\Entity\Coworking\Place;
use App\Form\Coworking\EventType;
use App\Security\Voter\EventVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\Coworking\EventRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\Coworking\ProductRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/coworking/event')]
#[IsGranted('ROLE_USER')]
class EventController extends AbstractController
{
    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(
        EventRepository $eventRepository,
        Request $request,
        Security $security
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        /** @var User $user */
        $user = $security->getUser();
        $userId = $user->getId();
        $canListAll = $security->isGranted(EventVoter::LIST_ALL);
        $events = $eventRepository->paginateRecipes($page, $canListAll ? null : $userId);

        return $this->render('coworking/event/index.html.twig', [
            'events' => $events,
            'availableToday' => $eventRepository->findAvailablePlacesByDate(new \DateTime('today')),
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        Cart $cartService,
        Security $security,
        ProductRepository $productRepository
    ): Response
    {
        $date = $request->query->get('date', 'today');
        $placeId = $request->query->get('placeId', null);
        $journee = $productRepository->findOneBy(['slug' => 'place-journee']);
        $demiJournee = $productRepository->findOneBy(['slug' => 'place-demi-journee']);
        /** @var User $user */
        $user = $security->getUser();
        $place = $entityManager->getRepository(Place::class)->findOneBy(['id' => $placeId]);
        $event = new Event();
        if ($date === 'dayAfterTomorrow') {
            $event->setStartEvent((new \DateTime('tomorrow'))->modify('+1 day')->setTime(8, 0));
        }else{
            $event->setStartEvent((new \DateTime($date))->setTime(8, 0));
        }
        if ($place) {
            $event->addPlace($place);
        }   
        $event->setBackgroundColor('#ff0000'); 
        $event->setTextColor('#000000'); 
        $event->setBorderColor('#00ff00');
        $event->setAllDay(false);
        $form = $this->createForm(EventType::class, $event, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = $form->getData();
            $user = $event->getUser();
            if(!$user instanceof User){
                /** @var User $user */
                $user = $this->getUser();
                $event->setUser($user);
            }
            if($user->getNom() != null && $user->getPrenom() != null){
                $titre = $user->getNom() . ' ' . $user->getPrenom();
            }else{
                $titre = 'Coworker #'.$user->getId();
            }
            $event->setTitle($titre);
            if($event->getDescription() == ""){
                $event->setDescription('Réservation de '.$this->getUser());
            }
            $entityManager->persist($event);
            $entityManager->flush();
            $contracts = $user->getContracts();
            if(count($contracts) > 0){
                foreach ($contracts as $contract) {
                    if($contract->getFlexi() > 0){
                        $contract->setFlexi($contract->getFlexi() - 1);
                        $entityManager->persist($contract);
                        $entityManager->flush();
                    }
                }
            }else{
                foreach ($event->getPlaces() as $place) {
                    if($event->getDuration() == "demi_journee"){
                        $cartService->addProduct($demiJournee->getId());
                    }else{
                        $cartService->addProduct($journee->getId());
                    }
                }
            }
            if($form->get('boissons')->getData()){
                foreach ($form->get('boissons')->getData() as $produit) {
                    $cartService->addProduct($produit->getId());
                }
            }
            $this->addFlash('success', 'Place bien réservée');

            return $this->redirectToRoute('app_cart_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('coworking/event/new.html.twig', [
            'event' => $event,
            'form' => $form,
            'place' => $place,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        return $this->render('coworking/event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    #[IsGranted('EVENT_EDIT', subject: 'event')]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EventType::class, $event, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('coworking/event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
    }
}
