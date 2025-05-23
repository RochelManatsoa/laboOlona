<?php

namespace App\Controller\Blog;

use App\Entity\Blog\Post;
use App\Entity\Blog\Comment;
use App\Entity\Blog\Category;
use App\Form\Blog\CommentType;
use App\Repository\Blog\PostRepository;
use App\Repository\Blog\CategoryRepository;
use App\Repository\Blog\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/blog')]
class BlogController extends AbstractController
{
    public function __construct(
        private PostRepository $postRepository, 
        private CategoryRepository $categoryRepository,
        private CommentRepository $commentRepository,
        private EntityManagerInterface $entityManager,
    ){}

    #[Route('/', name: 'app_blog_index', options: ['sitemap' => true])]
    public function index(
        Request $request, 
    ): Response
    {
        $page = $request->query->getInt('page', 1);

        return $this->render('blog/main/index.html.twig', [
            'posts' => $this->postRepository->paginatePosts($page),
            'categories' => $this->categoryRepository->findPublished(),
        ]);
    }

    #[Route('/{slug}', name: 'app_blog_view')]
    public function blog(Request $request, Post $post): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setPost($post);
            $this->entityManager->persist($comment);
            $this->entityManager->flush();
            $this->addFlash('success', 'Votre commentaire a été bien envoyé. Nous vous répondrons dans les plus brefs délais');
            return $this->redirectToRoute('app_blog_view', ['slug' => $post->getSlug()]);
        }

        return $this->render('blog/main/view.html.twig', [
            'post' => $post,
            'posts' => $this->postRepository->lastPosts(),
            'comments' => $this->commentRepository->findValidComments($post),
            'categories' => $this->categoryRepository->findPublished(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/category/{slug}', name: 'app_blog_category')]
    public function category(
        Request $request, 
        Category $category,
    ): Response
    {
        $page = $request->query->getInt('page', 1);

        return $this->render('blog/main/category.html.twig', [
            'category' => $category,
            'categories' => $this->categoryRepository->findPublished(),
            'posts' => $this->postRepository->paginatePosts($page, $category),
        ]);
    }
}
