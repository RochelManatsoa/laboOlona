<?php

namespace App\Controller\V2;

use App\Entity\User;
use App\Entity\Notification;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\BusinessModel\Credit;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Manager\BusinessModel\CreditManager;
use App\Repository\Finance\ContratRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BusinessModel\PurchasedContact;
use App\Manager\NotificationManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/dashboard')]
class ContactController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private ContratRepository $contratRepository,
        private PaginatorInterface $paginator,
        private ProfileManager $profileManager,
        private CreditManager $creditManager,
        private NotificationManager $notificationManager,
        private UrlGeneratorInterface $urlGeneratorInterface,
    ){}
    
    #[Route('/contacts', name: 'app_v2_contacts')]
    public function contact(Request $request): Response
    {
        $routeInfo = $this->userService->getRedirectRoute($this->getUser(), $request);
        $routeInfo['params'] = [];
        
        return $this->redirectToRoute($routeInfo['route'], $routeInfo['params']);
        
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $hasProfile = $this->userService->checkUserProfile($currentUser);
        if($hasProfile === null){
            return $this->redirectToRoute('app_v2_dashboard');
        }
        $purchasedContacts = $this->em->getRepository(PurchasedContact::class)->findContactsByBuyerAndStatus($currentUser, true);
        $pendingContacts = $this->em->getRepository(PurchasedContact::class)->findContactsByBuyerAndStatus($currentUser, false);
        $allContacts = $this->em->getRepository(PurchasedContact::class)->findBy([
            'buyer' => $currentUser,
        ], ['id' => 'DESC']);
        
        return $this->render('v2/dashboard/contacts/index.html.twig', [
            'allContacts' => $this->paginator->paginate(
                $allContacts,
                $request->query->getInt('page', 1),
                10
            ),
            'contacts' => $this->paginator->paginate(
                $purchasedContacts,
                $request->query->getInt('page', 1),
                10
            ),
            'pendingContacts' => $this->paginator->paginate(
                $pendingContacts,
                $request->query->getInt('page', 1),
                10
            ),
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_candidates'),
        ]);
    }

    #[Route('/contact/view/{purchasedContact}', name: 'app_v2_contact_view')]
    public function view(Request $request, PurchasedContact $purchasedContact): Response
    {
        return $this->render('v2/dashboard/contacts/view.html.twig', [
            'contact' => $purchasedContact->getContact(),
            'action' => $this->urlGeneratorInterface->generate('app_olona_talents_candidates'),
        ]);
    }

    #[Route('/contact/delete/{contact}', name: 'app_v2_contact_delete', methods: ['POST', 'GET'])]
    public function delete(Request $request, PurchasedContact $contact): Response
    {
        $contactId = $contact->getId();
        $message = "La contact a bien été supprimée";
        $this->em->remove($contact);
        $this->em->flush();
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/contacts/delete.html.twig', [
                'contactId' => $contactId,
                'message' => $message,
            ]);
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_v2_dashboard');
    }

    #[Route('/contact/show', name: 'app_v2_contact_show', methods: ['POST', 'GET'])]
    public function showContact(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $hasProfile = $this->userService->checkUserProfile($currentUser);
        if($hasProfile === null){
            return $this->redirectToRoute('app_v2_dashboard');
        }
        $contactId = $request->request->get('contactId');
        $showContactPrice = $request->request->get('showContactPrice');
        $actionView = $request->request->get('actionView');
        $contact = $this->em->getRepository(User::class)->find($contactId);
        $message = 'Contact ajouté dans votre réseau professionnel';
        $success = true;
        $status = 'Succès';
    
        $creditAmount = $this->profileManager->getCreditAmount($actionView);
    
        $recruiter = $this->em->getRepository(EntrepriseProfile::class)->findOneBy(['entreprise' => $contactId]);
        $candidat = $this->em->getRepository(CandidateProfile::class)->findOneBy(['candidat' => $contactId]);
        if ($creditAmount !== (float) $showContactPrice) {
            $message = "Formulaire invalide";
            $success = false;
            $status = 'Echec';
            $purchasedContact = null;
        }else{
            $response = $this->creditManager->adjustCredits($currentUser, $creditAmount, "Affichage de contact");
            if (isset($response['error'])) {
                $message = $response['error'];
                $success = false;
                $status = 'Echec';
            }else{
                $purchasedContact = new PurchasedContact();
                $purchasedContact->setBuyer($currentUser);
                $purchasedContact->setPurchaseDate(new \DateTime());
                $purchasedContact->setContact($contact);
                $purchasedContact->setPrice($creditAmount);
                $purchasedContact->setIsAccepted(true);
                $this->em->persist($purchasedContact);
                $this->em->flush();
            }
        }

        
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('v2/turbo/live.html.twig', [
                'message' => $message,
                'success' => $success,
                'status' => $status,
                'recruiter' => $recruiter,
                'purchasedContact' => $purchasedContact,
                'candidat' => $candidat,
                'user' => $contact,
                'credit' => $currentUser->getCredit()->getTotal(),
            ]);
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_v2_dashboard');
    }
}
