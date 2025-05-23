<?php

namespace App\Controller\Moderateur;

use App\Entity\BusinessModel\Subcription;
use App\Form\BusinessModel\SubcriptionForm;
use App\Repository\BusinessModel\SubcriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderateur/subcription')]
final class SubcriptionController extends AbstractController
{
    #[Route(name: 'app_moderateur_subcription_index', methods: ['GET'])]
    public function index(Request $request, SubcriptionRepository $subcriptionRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        return $this->render('moderateur/subcription/index.html.twig', [
            'subcriptions' => $subcriptionRepository->paginateSubcriptions($page),
        ]);
    }

    #[Route('/new', name: 'app_moderateur_subcription_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $subcription = new Subcription();
        $form = $this->createForm(SubcriptionForm::class, $subcription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($subcription);
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_subcription_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/subcription/new.html.twig', [
            'subcription' => $subcription,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderateur_subcription_show', methods: ['GET'])]
    public function show(Subcription $subcription): Response
    {
        return $this->render('moderateur/subcription/show.html.twig', [
            'subcription' => $subcription,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_moderateur_subcription_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Subcription $subcription, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SubcriptionForm::class, $subcription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_subcription_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/subcription/edit.html.twig', [
            'subcription' => $subcription,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderateur_subcription_delete', methods: ['POST'])]
    public function delete(Request $request, Subcription $subcription, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$subcription->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($subcription);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_moderateur_subcription_index', [], Response::HTTP_SEE_OTHER);
    }
}
