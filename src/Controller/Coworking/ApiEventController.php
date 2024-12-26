<?php

namespace App\Controller\Coworking;

use App\Entity\Coworking\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiEventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ){}

    #[Route('/coworking/api/event/{id}/edit', name: 'app_api_event_edit', methods: ['PUT'])]
    public function edit(?Event $event, Request $request): Response
    {
        $donnees = json_decode($request->getContent(), true);
        if (
            isset($donnees['title']) && !empty($donnees['title']) &&
            isset($donnees['description']) && !empty($donnees['description']) &&
            isset($donnees['start']) && !empty($donnees['start']) &&
            array_key_exists('allDay', $donnees) && 
            array_key_exists('end', $donnees) && 
            isset($donnees['backgroundColor']) && !empty($donnees['backgroundColor']) &&
            isset($donnees['borderColor']) && !empty($donnees['borderColor']) &&
            isset($donnees['textColor']) && !empty($donnees['textColor'])
        ) {
            $code = 200;
            $message = "Réservation modifiée avec succès";
            if (!$event) {
                $event = new Event();
                $code = 201;
                $message = "La réservation a été créée";
            }else{
                $event->setUpdatedAt(new \DateTime());
            }
            $event->setTitle($donnees['title']);
            $event->setDescription($donnees['description']);
            $event->setStartEvent(new \DateTime($donnees['start']));
            $event->setEndEvent(new \DateTime($donnees['end']));
            if ($donnees['allDay']) {
                $event->setEndEvent(new \DateTime($donnees['start']));
            }
            $event->setAllDay($donnees['allDay']);
            $event->setBackgroundColor($donnees['backgroundColor']);
            $event->setBorderColor($donnees['borderColor']);
            $event->setTextColor($donnees['textColor']);
            $this->em->persist($event);
            $this->em->flush();

            return $this->json([
                'status' => 'success',
                'message' => $message,
                'code' => $code,
                'donnees' => $donnees,
            ]);
        }else {
            return $this->json([
                'status' => 'error',
                'message' => 'Les données sont incomplètes',
                'code' => 400,
                'donnees' => $donnees,
            ]);
        }
    }
}
