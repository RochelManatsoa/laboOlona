<?php

namespace App\Controller\Dashboard\Moderateur;

use DateTime;
use App\Entity\Notification;
use App\Entity\ReferrerProfile;
use App\Twig\ReferrerExtension;
use App\Service\User\UserService;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use App\Manager\Referrer\ReferenceManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\NotificationProfileType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/coopteur')]
class CoopteurController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private ReferenceManager $referenceManager,
        private PaginatorInterface $paginatorInterface,
        private ReferrerExtension $referrerExtension,
        private MailerService $mailerService,
        private UrlGeneratorInterface $urlGenerator,
    ) {}
    
    #[Route('/{customId}', name: 'app_dashboard_moderateur_coopteur_view')]
    public function view(Request $request, ReferrerProfile $referrerProfile): Response
    {
        $references = $this->referenceManager->getReferenceAnnonce($referrerProfile->getReferrals()->toArray());

        $notification = new Notification();
        $notification->setDateMessage(new DateTime());
        $notification->setExpediteur($this->userService->getCurrentUser());
        $notification->setDestinataire($referrerProfile->getReferrer());
        $notification->setType(Notification::TYPE_PROFIL);
        $notification->setIsRead(false);
        $notification->setTitre("Information sur votre profil Coopteur Olona Talents");
        $notification->setContenu(
            "
            <p>Bonjour [Nom de l'Utilisateur],</p><p>Nous avons récemment examiné votre profil sur <strong>Olona Talents </strong>et avons remarqué qu'il manque certaines informations essentielles pour que votre profil soit pleinement actif et visible pour les autres utilisateurs.</p><p>Pour assurer l'efficacité et la qualité de nos services, il est important que chaque profil soit complet et à jour. Voici les informations manquantes :</p><ol><li>[Information manquante 1]</li><li>[Information manquante 2]</li><li>[Autres informations manquantes, si nécessaire]</li></ol><p>Vous pouvez mettre à jour votre profil en vous connectant à votre compte et en naviguant vers la section [Nom de la section appropriée]. La mise à jour de ces informations augmentera vos chances de [objectif ou avantage lié à l'utilisation du site] .</p><p>Si vous avez besoin d'aide ou si vous avez des questions concernant la mise à jour de votre profil, n'hésitez pas à nous contacter. Nous sommes là pour vous aider.</p><p>Nous vous remercions pour votre attention à ce détail et nous sommes impatients de vous voir tirer pleinement parti de tout ce que <strong>Olona Talents</strong> a à offrir.</p>
            "
        );

        $form = $this->createForm(NotificationProfileType::class, $notification);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $notification = $form->getData();
            $this->em->persist($notification);
            $this->em->flush();
            /** Envoi email à l'utilisateur */
            $this->mailerService->send(
                $referrerProfile->getReferrer()->getEmail(),
                $notification->getTitre(),
                "moderateur/notification_profile.html.twig",
                [
                    'user' => $referrerProfile->getReferrer(),
                    'contenu' => $notification->getContenu(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_candidat_compte', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            $this->addFlash('success', 'Un email a été envoyé au candidat');

        }
        return $this->render('dashboard/moderateur/coopteur/view.html.twig', [
            'coopteur' => $referrerProfile,
            'references' => $references,
            'form' => $form->createView(),
        ]);
    }
}
