<?php

namespace App\Controller\Coworking;

use App\Entity\Coworking\Contract;
use App\Form\Coworking\ContractType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Coworking\ContractRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/coworking/contract')]
#[IsGranted('ROLE_ADMIN')]
class ContractController extends AbstractController
{
    #[Route('/', name: 'app_coworking_contract_index', methods: ['GET'])]
    public function index(Request $request, ContractRepository $contractRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        return $this->render('coworking/contract/index.html.twig', [
            'contracts' => $contractRepository->paginateContracts($page),
        ]);
    }

    #[Route('/new', name: 'app_coworking_contract_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contract = new Contract();
        $form = $this->createForm(ContractType::class, $contract, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contract);
            $entityManager->flush();

            return $this->redirectToRoute('app_coworking_contract_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('coworking/contract/new.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_coworking_contract_show', methods: ['GET'])]
    public function show(Contract $contract): Response
    {
        return $this->render('coworking/contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_coworking_contract_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contract $contract, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContractType::class, $contract, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_coworking_contract_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('coworking/contract/edit.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_coworking_contract_delete', methods: ['POST'])]
    public function delete(Request $request, Contract $contract, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contract->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contract);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_coworking_contract_index', [], Response::HTTP_SEE_OTHER);
    }
}
