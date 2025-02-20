<?php

namespace App\Controller\Marketing;

use App\Entity\Marketing\Source;
use App\Form\Marketing\SourceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\Marketing\SourceRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/moderateur/marketing/source')]
class SourceController extends AbstractController
{
    public function __construct(private SluggerInterface $sluggerInterface)
    {
        $this->sluggerInterface = $sluggerInterface;
    }

    #[Route('/', name: 'app_marketing_source_index', methods: ['GET'])]
    public function index(
        Request $request, 
        SourceRepository $sourceRepository
    ): Response
    {
        $page = $request->query->getInt('page', 1);

        return $this->render('marketing/source/index.html.twig', [
            'sources' => $sourceRepository->paginateLeads($page),
        ]);
    }

    #[Route('/new', name: 'app_marketing_source_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $source = new Source();
        $form = $this->createForm(SourceType::class, $source);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $source->setSlug($this->sluggerInterface->slug(strtolower($source->getName())));
            $entityManager->persist($source);
            $entityManager->flush();

            return $this->redirectToRoute('app_marketing_source_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('marketing/source/new.html.twig', [
            'source' => $source,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_marketing_source_show', methods: ['GET'])]
    public function show(Source $source): Response
    {
        return $this->render('marketing/source/show.html.twig', [
            'source' => $source,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_marketing_source_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Source $source, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SourceType::class, $source);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $source->setSlug($this->sluggerInterface->slug(strtolower($source->getName())));
            $entityManager->flush();

            return $this->redirectToRoute('app_marketing_source_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('marketing/source/edit.html.twig', [
            'source' => $source,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_marketing_source_delete', methods: ['POST'])]
    public function delete(Request $request, Source $source, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$source->getId(), $request->request->get('_token'))) {
            $entityManager->remove($source);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_marketing_source_index', [], Response::HTTP_SEE_OTHER);
    }
}
