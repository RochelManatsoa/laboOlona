<?php

namespace App\WhiteLabel\Controller\Client1\Crud;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use App\WhiteLabel\Form\Client1\EntrepriseProfileType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/wl-admin/recruiter')]
class EntrepriseProfileController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginatorInterface
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    #[Route('/', name: 'app_white_label_entreprise_profile_index', methods: ['GET'])]
    public function index(
        Request $request, 
        Security $security
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        /** @var User $user */
        $user = $security->getUser();
        $userId = $user->getId();
        $canListAll = true;
        $entreprise_profiles = $this->entityManager->getRepository(EntrepriseProfile::class)->paginateRecipes($page, $this->paginatorInterface, $canListAll ? null : $userId);
        // dd($entreprise_profiles);
        return $this->render('white_label/client1/admin/recruiter/index.html.twig', [
            'entreprise_profiles' => $entreprise_profiles,
        ]);
    }

    #[Route('/new', name: 'app_white_label_entreprise_profile_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
    ): Response
    {
        $entrepriseProfile = new EntrepriseProfile();
        $form = $this->createForm(EntrepriseProfileType::class, $entrepriseProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entrepriseProfile);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_white_label_entreprise_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('white_label/client1/admin/recruiter/new.html.twig', [
            'entreprise_profile' => $entrepriseProfile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_white_label_entreprise_profile_show', methods: ['GET'])]
    public function show(EntrepriseProfile $entrepriseProfile): Response
    {
        return $this->render('white_label/client1/admin/recruiter/show.html.twig', [
            'entreprise_profile' => $entrepriseProfile,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_white_label_entreprise_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        EntrepriseProfile $entrepriseProfile, 
    ): Response
    {
        $form = $this->createForm(EntrepriseProfileType::class, $entrepriseProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_white_label_entreprise_profile_show', ['id' => $entrepriseProfile->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('white_label/client1/admin/recruiter/edit.html.twig', [
            'entreprise_profile' => $entrepriseProfile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_white_label_entreprise_profile_delete', methods: ['POST'])]
    public function delete(Request $request, EntrepriseProfile $entrepriseProfile, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entrepriseProfile->getId(), $request->request->get('_token'))) {
            $entityManager->remove($entrepriseProfile);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_white_label_entreprise_profile_index', [], Response::HTTP_SEE_OTHER);
    }
}
