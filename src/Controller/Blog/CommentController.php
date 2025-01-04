<?php

namespace App\Controller\Blog;

use App\Entity\Blog\Comment;
use App\Form\Blog\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Blog\CommentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/blog/admin/comment')]
#[IsGranted('ROLE_ADMIN_BLOG')]
class CommentController extends AbstractController
{
    #[Route('/', name: 'app_blog_comment_index', methods: ['GET'])]
    public function index(Request $request, CommentRepository $commentRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        return $this->render('blog/comment/index.html.twig', [
            'comments' => $commentRepository->paginateComments($page),
        ]);
    }

    #[Route('/new', name: 'app_blog_comment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_blog_comment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('blog/comment/new.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_blog_comment_show', methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->render('blog/comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_blog_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_blog_comment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('blog/comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_blog_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_blog_comment_index', [], Response::HTTP_SEE_OTHER);
    }
}
