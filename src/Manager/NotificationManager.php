<?php

namespace App\Manager;

use DateTime;
use App\Entity\User;
use App\Entity\Notification;
use Twig\Environment as Twig;
use App\Entity\CandidateProfile;
use Symfony\Component\Form\Form;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Entity\Moderateur\Metting;
use App\Entity\Entreprise\JobListing;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class NotificationManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private NotificationRepository $notificationRepository,
        private RequestStack $requestStack,
        private ModerateurManager $moderateurManager,
        private Security $security
    ){}

    public function createNotification(
        User $expediteur, 
        User $destinataire = null,
        string $type,
        string $titre,
        string $contenu,
    ) : Notification
    {
        $notification = new Notification();
        $notification->setExpediteur($expediteur);
        $notification->setDestinataire($destinataire);
        $notification->setDateMessage(new DateTime());
        $notification->setIsRead(false);
        $notification->setStatus($type);
        $notification->setTitre($titre);
        $notification->setContenu($contenu);
        $this->save($notification);

        return $notification;
    }

    public function save(Notification $notification)
    {
		$this->em->persist($notification);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $this->save($form->getData());
    }

    public function notifyModerateurs(
        User $expediteur,
        string $type,
        string $titre,
        string $contenu,
    ): void
    {
        $moderateurs = $this->moderateurManager->getModerateurs();
        foreach ($moderateurs as $moderateur) {
            $this->createNotification(
                $expediteur,
                $moderateur,
                $type,
                $titre,
                $contenu,
            );
        }
    }
}