<?php

namespace App\Controller\Facebook;

use App\Entity\Facebook\Contest;
use App\Form\Facebook\ContestType;
use App\Repository\Facebook\ContestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/facebook/contest')]
class ContestController extends AbstractController
{
    #[Route('/', name: 'app_facebook_contest_index', methods: ['GET'])]
    public function index(
        Request $request, 
        ContestRepository $contestRepository,
    ): Response
    {
        $page = $request->query->getInt('page', 1);

        return $this->render('facebook/contest/index.html.twig', [
            'contests' => $contestRepository->paginateContests($page),
        ]);
    }

    #[Route('/new', name: 'app_facebook_contest_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        SluggerInterface $slugger,
        EntityManagerInterface $entityManager
    ): Response
    {
        $contest = new Contest();
        $form = $this->createForm(ContestType::class, $contest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contest->setSlug($slugger->slug($contest->getName()));
            $entityManager->persist($contest);
            $entityManager->flush();

            return $this->redirectToRoute('app_facebook_contest_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('facebook/contest/new.html.twig', [
            'contest' => $contest,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_facebook_contest_show', methods: ['GET'])]
    public function show(Contest $contest): Response
    {
        return $this->render('facebook/contest/show.html.twig', [
            'contest' => $contest,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_facebook_contest_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Contest $contest, 
        SluggerInterface $slugger,
        EntityManagerInterface $entityManager
    ): Response
    {
        $form = $this->createForm(ContestType::class, $contest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contest->setSlug($slugger->slug($contest->getName()));
            $contest->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('app_facebook_contest_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('facebook/contest/edit.html.twig', [
            'contest' => $contest,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_facebook_contest_delete', methods: ['POST'])]
    public function delete(Request $request, Contest $contest, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contest->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contest);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_facebook_contest_index', [], Response::HTTP_SEE_OTHER);
    }
}
