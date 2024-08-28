<?php

namespace App\Controller\V2\Candidate;

use App\Entity\User;
use App\Entity\Finance\Contrat;
use App\Manager\ProfileManager;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/candidate/contact')]
class ContactController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private ContratRepository $contratRepository,
        private PaginatorInterface $paginator,
        private ProfileManager $profileManager,
        private CreditManager $creditManager,
    ){}
    
    #[Route('/', name: 'app_v2_candidate_contact')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $purchasedContacts = $currentUser->getPurchasedContacts();
        
        return $this->render('v2/dashboard/candidate/contact/index.html.twig', [
            'contacts' => $this->paginator->paginate(
                $purchasedContacts,
                $request->query->getInt('page', 1),
                20
            )
        ]);
    }

    #[Route('/view/{purchasedContact}', name: 'app_v2_candidate_contact_view')]
    public function view(PurchasedContact $purchasedContact): Response
    {
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        
        return $this->render('v2/dashboard/candidate/contact/view.html.twig', [
            'contact' => $purchasedContact->getContact(),
        ]);
    }

    #[Route('/delete/{contact}', name: 'app_v2_candidate_contact_delete', methods: ['POST', 'GET'])]
    public function delete(Request $request, PurchasedContact $contact): Response
    {
        $contactId = $contact->getId();
        $message = "La contact a bien été supprimée";
        $this->em->remove($contact);
        $this->em->flush();
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/candidate/contact/delete.html.twig', [
                'contactId' => $contactId,
                'message' => $message,
            ]);
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_v2_candidate_contact');
    }

    #[Route('/show-contact', name: 'app_v2_candidate_contact_show_recruiter', methods: ['POST', 'GET'])]
    public function showContact(Request $request): Response
    {
        $this->denyAccessUnlessGranted('CANDIDAT_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux candidats uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        $recruiterId = $request->request->get('recruiterId');
        $message = 'Contact de l\'entreprise affiché';
        $success = true;
        $status = 'Succès';
    
        $creditAmount = $this->profileManager->getCreditAmount(Credit::ACTION_VIEW_CANDIDATE);
        $response = $this->creditManager->adjustCredits($currentUser, $creditAmount);
    
        if (isset($response['error'])) {
            $message = $response['error'];
            $success = false;
            $status = 'Echec';
        }
    
        $recruiter = $this->em->getRepository(EntrepriseProfile::class)->find($recruiterId);
        if (!$recruiter) {
            $message = 'Entreprise non trouvé.';
            $success = false;
            $status = 'Echec';
        }

        $purchasedContact = new PurchasedContact();
        $purchasedContact->setBuyer($currentUser);
        $purchasedContact->setPurchaseDate(new \DateTime());
        $purchasedContact->setContact($recruiter->getEntreprise());
        $purchasedContact->setPrice($creditAmount);
        $this->em->persist($purchasedContact);
        $this->em->flush();
        
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('v2/dashboard/candidate/live.html.twig', [
                'message' => $message,
                'success' => $success,
                'status' => $status,
                'user' => $recruiter->getEntreprise(),
                'credit' => $currentUser->getCredit()->getTotal(),
            ]);
        }

        // if ($request->isXmlHttpRequest()) {
        //     return $this->json([
        //         'message' => $message,
        //         'success' => $success,
        //         'status' => $status,
        //         'user' => $recruiter->getEntreprise(),
        //         'credit' => $currentUser->getCredit()->getTotal(),
        //     ], 200, [], ['groups' => 'user']);
        // }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_v2_candidate_contact');
    }
}
