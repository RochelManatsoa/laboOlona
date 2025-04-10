<?php

namespace App\Controller\Moderateur;

use App\Entity\Coworking\Contract;
use App\Form\Coworking\ContractPremiumType;
use App\Manager\Coworking\ContractManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Coworking\ContractRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/moderateur/contract')]
class ContractController extends AbstractController
{
    #[Route('/', name: 'app_contract_index', methods: ['GET'])]
    public function index(Request $request, ContractRepository $contractRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        return $this->render('moderateur/contract/index.html.twig', [
            'contracts' => $contractRepository->paginatePremiumContracts($page),
        ]);
    }

    #[Route('/new', name: 'app_contract_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ContractManager $contractManager): Response
    {
        $contract = $contractManager->init();
        $form = $this->createForm(ContractPremiumType::class, $contract, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contractManager->saveForm($form);

            return $this->redirectToRoute('app_contract_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/contract/new.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contract_show', methods: ['GET'])]
    public function show(Contract $contract): Response
    {
        return $this->render('moderateur/contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contract_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contract $contract, ContractManager $contractManager): Response
    {
        $form = $this->createForm(ContractPremiumType::class, $contract, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contractManager->saveForm($form);

            return $this->redirectToRoute('app_contract_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/contract/edit.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contract_delete', methods: ['POST'])]
    public function delete(Request $request, Contract $contract, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contract->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contract);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_contract_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/contract/{contractNumber}/invoice', name: 'app_contract_invoice')]
    public function invoice(Contract $contract, ContractManager $contractManager)
    {
        if (!$contractManager->checkIfTransactionSuccess($contract)) {
            return $this->redirectToRoute('app_contract_edit', [ 'id' => $contract->getId() ]);
        }
        $file = $contractManager->generateFacture($contract);

        return new BinaryFileResponse($file);
    }

    #[Route('/pdf/{contractNumber}', name: 'app_contract_pdf')]
    public function contractPdf(Contract $contract, ContractManager $contractManager)
    {
        if (!$contractManager->checkIfTransactionSuccess($contract)) {
            return $this->redirectToRoute('app_contract_edit', [ 'id' => $contract->getId() ]);
        }
        $file = $contractManager->generateContract($contract);

        return new BinaryFileResponse($file);
    }
}
