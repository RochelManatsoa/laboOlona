<?php

namespace App\Controller\Blog;

use App\Entity\Blog\Category;
use App\Form\Blog\CategoryType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Blog\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/blog/admin/category')]
#[IsGranted('ROLE_ADMIN_BLOG')]
class CategoryController extends AbstractController
{
    public function __construct(
        private SluggerInterface $sluggerInterface
    ){}

    #[Route('/', name: 'app_blog_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('blog/category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_blog_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();
            $category->setSlug($this->sluggerInterface->slug(strtolower($category->getTitle())));
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('app_blog_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('blog/category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_blog_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('blog/category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_blog_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();
            $category->setSlug($this->sluggerInterface->slug(strtolower($category->getTitle())));
            $entityManager->flush();

            return $this->redirectToRoute('app_blog_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('blog/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_blog_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_blog_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
