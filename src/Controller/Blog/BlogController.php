<?php

namespace App\Controller\Blog;

use App\Entity\Blog\Category;
use App\Entity\Blog\Post;
use App\Repository\Blog\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/blog')]
class BlogController extends AbstractController
{
    #[Route('/', name: 'app_blog_index', options: ['sitemap' => true])]
    public function index(Request $request, PostRepository $postRepository): Response
    {
        $page = $request->query->getInt('page', 1);

        return $this->render('blog/main/index.html.twig', [
            'posts' => $postRepository->paginatePosts($page),
        ]);
    }

    #[Route('/{slug}', name: 'app_blog_view', options: ['sitemap' => true])]
    public function blog(Request $request, Post $post): Response
    {
        return $this->render('blog/main/view.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/category/{slug}', name: 'app_blog_category', options: ['sitemap' => true])]
    public function category(Request $request, Category $category): Response
    {
        return $this->render('blog/main/category.html.twig', [
            'category' => $category,
        ]);
    }
}
