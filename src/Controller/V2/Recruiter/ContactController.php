<?php

namespace App\Controller\V2\Recruiter;

use App\Entity\Finance\Contrat;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\BusinessModel\PurchasedContact;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/contract')]
class ContactController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private PaginatorInterface $paginator,
    ){}
    
    #[Route('/', name: 'app_v2_recruiter_contact')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $purchasedContacts = $user->getPurchasedContacts();
        
        return $this->render('v2/dashboard/recruiter/contact/index.html.twig', [
            'contacts' => $this->paginator->paginate(
                $purchasedContacts,
                $request->query->getInt('page', 1),
                20
            )
        ]);
    }

    #[Route('/view/{purchasedContact}', name: 'app_v2_recruiter_contact_view')]
    public function view(Request $request, PurchasedContact $purchasedContact): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        
        return $this->render('v2/dashboard/recruiter/contact/view.html.twig', [
            'contact' => $purchasedContact->getContact(),
        ]);
    }

    #[Route('/delete/{contact}', name: 'app_v2_recruiter_contact_delete', methods: ['POST', 'GET'])]
    public function delete(Request $request, PurchasedContact $contact): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $contactId = $contact->getId();
        $message = "La contact a bien été supprimée";
        $this->em->remove($contact);
        $this->em->flush();
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/recruiter/contact/delete.html.twig', [
                'contactId' => $contactId,
                'message' => $message,
            ]);
        }

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_v2_recruiter_contact');
    }
}
