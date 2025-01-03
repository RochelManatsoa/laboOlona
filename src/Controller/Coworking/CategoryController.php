<?php

namespace App\Controller\Coworking;

use App\Entity\Coworking\Category;
use App\Form\Coworking\CategoryType;
use App\Repository\Coworking\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/coworking/category')]
#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'app_coworking_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('coworking/category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_coworking_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $slugger->slug($category->getName());
            $category->setSlug(strtolower($slug));
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('app_coworking_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('coworking/category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_coworking_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('coworking/category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_coworking_category_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        SluggerInterface $slugger, 
        Category $category, 
        EntityManagerInterface $entityManager
    ): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $slugger->slug($category->getName());
            $category->setSlug(strtolower($slug));
            $category->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('app_coworking_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('coworking/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_coworking_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_coworking_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
