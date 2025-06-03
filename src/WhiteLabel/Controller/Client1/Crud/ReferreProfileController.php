<?php

namespace App\WhiteLabel\Controller\Client1\Crud;

use App\WhiteLabel\Entity\Client1\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\WhiteLabel\Entity\Client1\ReferrerProfile;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use App\WhiteLabel\Form\Client1\ReferrerProfileType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/wl-admin/employe')]
class ReferreProfileController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginatorInterface
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    #[Route('/', name: 'app_white_label_employe_profile_index', methods: ['GET'])]
    public function index(
        Request $request, 
        Security $security
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        $ref = $request->query->get('ref', null);
        $affiliateId = null;
        if($ref){
            $referrer = $this->entityManager->getRepository(ReferrerProfile::class)->findOneBy(['customId' => $ref]);
            if ($referrer) {
                $affiliateId = $referrer->getReferrer()->getId();
            }
        }
        /** @var User $user */
        $user = $security->getUser();
        $userId = $user->getId();
        $canListAll = true;
        $employes = $this->entityManager->getRepository(ReferrerProfile::class)->paginateRecipes($page, $this->paginatorInterface, $canListAll ? null : $userId, $affiliateId);

        return $this->render('white_label/client1/admin/employe/index.html.twig', [
            'employes' => $employes,
        ]);
    }

    #[Route('/new', name: 'app_white_label_employe_profile_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
    ): Response
    {
        $referrerProfile = new ReferrerProfile();
        $form = $this->createForm(ReferrerProfileType::class, $referrerProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($referrerProfile);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_white_label_employe_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('white_label/client1/admin/employe/new.html.twig', [
            'employe' => $referrerProfile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_white_label_employe_profile_show', methods: ['GET'])]
    public function show(ReferrerProfile $referrerProfile): Response
    {
        return $this->render('white_label/client1/admin/employe/show.html.twig', [
            'employe' => $referrerProfile,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_white_label_employe_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        ReferrerProfile $referrerProfile,
    ): Response
    {
        $form = $this->createForm(ReferrerProfileType::class, $referrerProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_white_label_employe_profile_show', ['id' => $referrerProfile->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('white_label/client1/admin/employe/edit.html.twig', [
            'employe' => $referrerProfile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_white_label_employe_profile_delete', methods: ['POST'])]
    public function delete(Request $request, ReferrerProfile $referrerProfile, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$referrerProfile->getId(), $request->request->get('_token'))) {
            $entityManager->remove($referrerProfile);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_white_label_candidat_profile_index', [], Response::HTTP_SEE_OTHER);
    }
}
