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
use App\WhiteLabel\Form\Client1\ApplicationsForm;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\WhiteLabel\Entity\Client1\Candidate\Applications;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/wl-admin/candidature')]
class CandidatureController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginatorInterface
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    #[Route('/', name: 'app_white_label_application_index', methods: ['GET'])]
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
        $applications = $this->entityManager->getRepository(Applications::class)->paginateRecipes($page, $this->paginatorInterface, $canListAll ? null : $userId);

        return $this->render('white_label/client1/admin/candidature/index.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/new', name: 'app_white_label_application_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
    ): Response
    {
        $application = new Applications();
        $form = $this->createForm(ApplicationsForm::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($application);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_white_label_application_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('white_label/client1/admin/candidature/new.html.twig', [
            'application' => $application,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_white_label_application_show', methods: ['GET'])]
    public function show(Applications $application): Response
    {
        return $this->render('white_label/client1/admin/candidature/show.html.twig', [
            'application' => $application,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_white_label_application_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Applications $application,
    ): Response
    {
        $form = $this->createForm(ApplicationsForm::class, $application, ['isEdit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_white_label_application_show', ['id' => $application->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('white_label/client1/admin/candidature/edit.html.twig', [
            'application' => $application,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_white_label_application_delete', methods: ['POST'])]
    public function delete(Request $request, Applications $application): Response
    {
        if ($this->isCsrfTokenValid('delete'.$application->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($application);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_white_label_application_index', [], Response::HTTP_SEE_OTHER);
    }
}
