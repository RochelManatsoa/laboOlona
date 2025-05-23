<?php

namespace App\Controller\Moderateur;

use App\Entity\Facebook\ContestEntry;
use App\Form\Facebook\ContestEntryType;
use App\Repository\Facebook\ContestEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/moderateur/facebook/entry/contest')]
class ContestEntryController extends AbstractController
{
    #[Route('/', name: 'app_moderateur_facebook_contest_entry_index', methods: ['GET'])]
    public function index(
        Request $request, 
        ContestEntryRepository $contestEntryRepository,
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status', ContestEntry::STATUS_SEND);

        return $this->render('moderateur/contest_entry/index.html.twig', [
            'contest_entries' => $contestEntryRepository->paginateContestEntries($page, $status),
            'count' => $contestEntryRepository->countAll(),
            'countStatus' => $contestEntryRepository->countStatus($status),
            'statuses' => ContestEntry::getStatuses(),
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/new', name: 'app_moderateur_facebook_contest_entry_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contestEntry = new ContestEntry();
        $form = $this->createForm(ContestEntryType::class, $contestEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contestEntry);
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_facebook_contest_entry_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/contest_entry/new.html.twig', [
            'contest_entry' => $contestEntry,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderateur_facebook_contest_entry_show', methods: ['GET'])]
    public function show(ContestEntry $contestEntry): Response
    {
        return $this->render('moderateur/contest_entry/show.html.twig', [
            'contest_entry' => $contestEntry,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_moderateur_facebook_contest_entry_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ContestEntry $contestEntry, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContestEntryType::class, $contestEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_moderateur_facebook_contest_entry_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/contest_entry/edit.html.twig', [
            'contest_entry' => $contestEntry,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_moderateur_facebook_contest_entry_delete', methods: ['POST'])]
    public function delete(Request $request, ContestEntry $contestEntry, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contestEntry->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contestEntry);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_moderateur_facebook_contest_entry_index', [], Response::HTTP_SEE_OTHER);
    }
}
