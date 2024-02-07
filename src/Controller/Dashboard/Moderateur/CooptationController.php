<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Entity\Referrer\Referral;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Form\Referrer\ReferralType;
use App\Manager\NotificationManager;
use App\Entity\Entreprise\JobListing;
use App\Entity\ReferrerProfile;
use App\Form\Moderateur\CooptationType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository;
use App\Repository\Referrer\ReferralRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/cooptation')]
class CooptationController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private ModerateurManager $moderateurManager,
        private NotificationManager $notificationManager,
        private NotificationRepository $notificationRepository,
        private PaginatorInterface $paginator,
    ) {}

    #[Route('/{referralCode}', name: 'app_dashboard_moderateur_cooptation_view')]
    public function view(Request $request, Referral $cooptation): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $form = $this->createForm(CooptationType::class, $cooptation);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $this->em->persist($form->getData());
            $this->em->flush();
            $this->addFlash('success', 'Cooptation mise à jour avec succès.');

            $referer = $request->headers->get('referer');
            return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_dashboard_moderateur_cooptation');
        }

        return $this->render('dashboard/moderateur/cooptation/view.html.twig', [
            'cooptation' => $cooptation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/annonce/{jobId}', name: 'app_dashboard_moderateur_cooptation_annonce')]
    public function annonce(JobListing $annonce, ReferralRepository $referralRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        
        return $this->render('dashboard/moderateur/cooptation/annonce.html.twig', [
            'annonce' => $annonce,
            'referrals' => $annonce->getReferrals(),
            'referralsByReferrer' => $referralRepository->getReferralsByReferrer($annonce->getId()),
        ]);
    }

    #[Route('/annonce/{jobId}/referrer/{customId}', name: 'app_dashboard_moderateur_cooptation_annonce_referrer')]
    public function referrer(JobListing $annonce, ReferrerProfile $referrerProfile, ReferralRepository $referralRepository): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        // dd($annonce, $referrerProfile);
        return $this->render('dashboard/moderateur/cooptation/referrer.html.twig', [
            'annonce' => $annonce,
            'coopteur' => $referrerProfile,
            'cooptations' => $referralRepository->getReferralsByReferrer($annonce->getId(), $referrerProfile->getId()),
        ]);
    }
}
