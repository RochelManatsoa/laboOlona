<?php

namespace App\Controller\Moderateur;

use App\Entity\AffiliateTool;
use App\Form\AffiliateToolType;
use App\Repository\AffiliateToolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('dashboard/moderateur/tool')]
class AffiliateToolController extends AbstractController
{
    #[Route('/', name: 'app_affiliate_tool_index', methods: ['GET'])]
    public function index(Request $request, AffiliateToolRepository $affiliateToolRepository): Response
    {
        $page = $request->query->get('page', 1);
        return $this->render('moderateur/affiliate_tool/index.html.twig', [
            'affiliate_tools' => $affiliateToolRepository->paginateTools($page),
        ]);
    }

    #[Route('/new', name: 'app_affiliate_tool_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        SluggerInterface $sluggerInterface
    ): Response
    {
        $affiliateTool = new AffiliateTool();
        $form = $this->createForm(AffiliateToolType::class, $affiliateTool);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $affiliateTool->setSlug($sluggerInterface->slug(strtolower($affiliateTool->getNom())));
            $entityManager->persist($affiliateTool);
            $entityManager->flush();

            return $this->redirectToRoute('app_affiliate_tool_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/affiliate_tool/new.html.twig', [
            'affiliate_tool' => $affiliateTool,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_affiliate_tool_show', methods: ['GET'])]
    public function show(AffiliateTool $affiliateTool): Response
    {
        return $this->render('moderateur/affiliate_tool/show.html.twig', [
            'affiliate_tool' => $affiliateTool,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_affiliate_tool_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        AffiliateTool $affiliateTool, 
        EntityManagerInterface $entityManager,
        SluggerInterface $sluggerInterface
    ): Response
    {
        $form = $this->createForm(AffiliateToolType::class, $affiliateTool);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $affiliateTool->setSlug($sluggerInterface->slug(strtolower($affiliateTool->getNom())));
            $entityManager->flush();

            return $this->redirectToRoute('app_affiliate_tool_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('moderateur/affiliate_tool/edit.html.twig', [
            'affiliate_tool' => $affiliateTool,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_affiliate_tool_delete', methods: ['POST'])]
    public function delete(Request $request, AffiliateTool $affiliateTool, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$affiliateTool->getId(), $request->request->get('_token'))) {
            $entityManager->remove($affiliateTool);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_affiliate_tool_index', [], Response::HTTP_SEE_OTHER);
    }
}
