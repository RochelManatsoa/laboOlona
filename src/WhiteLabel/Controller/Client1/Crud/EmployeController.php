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
use App\WhiteLabel\Entity\Client1\Finance\Employe;
use App\WhiteLabel\Form\Client1\Finance\EmployeType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_ADMIN')]
#[Route('/wl-admin/employe')]
class EmployeController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginatorInterface
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
    }
    
    #[Route('/', name: 'app_white_label_employe_index', methods: ['GET'])]
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
        $employes = $this->entityManager->getRepository(Employe::class)->paginateRecipes($page, $this->paginatorInterface, $canListAll ? null : $userId);

        return $this->render('white_label/client1/admin/finance_employe/index.html.twig', [
            'employes' => $employes,
        ]);
    }

    #[Route('/new', name: 'app_white_label_employe_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
    ): Response
    {
        $employe = new Employe();
        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($employe);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_white_label_employe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('white_label/client1/admin/finance_employe/new.html.twig', [
            'employe' => $employe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_white_label_employe_show', methods: ['GET'])]
    public function show(Employe $employe): Response
    {
        return $this->render('white_label/client1/admin/finance_employe/show.html.twig', [
            'employe' => $employe,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_white_label_employe_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Employe $employe,
    ): Response
    {
        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_white_label_employe_show', ['id' => $employe->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('white_label/client1/admin/finance_employe/edit.html.twig', [
            'employe' => $employe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_white_label_employe_delete', methods: ['POST'])]
    public function delete(Request $request, Employe $employe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$employe->getId(), $request->request->get('_token'))) {
            $entityManager->remove($employe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_white_label_employe_index', [], Response::HTTP_SEE_OTHER);
    }
}
