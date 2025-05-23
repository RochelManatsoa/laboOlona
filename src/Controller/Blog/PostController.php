<?php

namespace App\Controller\Blog;

use App\Entity\Blog\Post;
use App\Form\Blog\PostType;
use App\Repository\Blog\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/blog/admin/post')]
#[IsGranted('ROLE_ADMIN_BLOG')]
class PostController extends AbstractController
{
    public function __construct(
        private SluggerInterface $sluggerInterface
    ){}
    
    #[Route('/', name: 'app_blog_post_index', methods: ['GET'])]
    public function index(
        Request $request, 
        PostRepository $postRepository,
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        
        return $this->render('blog/post/index.html.twig', [
            'posts' => $postRepository->paginateAllPosts($page),
        ]);
    }

    #[Route('/new', name: 'app_blog_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $post->setSlug($this->sluggerInterface->slug(strtolower($post->getTitle())));
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('app_blog_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('blog/post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_blog_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        return $this->render('blog/post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_blog_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PostType::class, $post, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $post->setSlug($this->sluggerInterface->slug(strtolower($post->getTitle())));
            $entityManager->flush();

            return $this->redirectToRoute('app_blog_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('blog/post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_blog_post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_blog_post_index', [], Response::HTTP_SEE_OTHER);
    }
}
