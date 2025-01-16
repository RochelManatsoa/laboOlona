<?php

namespace App\Twig;

use App\Entity\Blog\Comment;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Entity\Blog\Post;
use Twig\Extension\AbstractExtension;
use App\Repository\Blog\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Blog\CommentRepository;
use App\Repository\Blog\CategoryRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class BlogExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
        private EntityManagerInterface $em,
        private PostRepository $postRepository,
        private CategoryRepository $categoryRepository,
        private CommentRepository $commentRepository,
    ) {}

    public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getPostStatus', [$this, 'getPostStatus']),
            new TwigFunction('getCommentStatus', [$this, 'getCommentStatus']),
        ];
    }

    public function getPostStatus(?string $status)
    {
        $labels = Post::getLabels();
        switch ($status) {
            case Post::STATUS_PENDING:
                return '<span class="badge bg-secondary rounded-pill">'.$labels[Post::STATUS_PENDING].'</span>';
                break;

            case Post::STATUS_VALIDATED:
                return '<span class="badge bg-success rounded-pill">'.$labels[Post::STATUS_VALIDATED].'</span>';
                break;
            
            case Post::STATUS_SCHEDULED:
                return '<span class="badge bg-danger rounded-pill">'.$labels[Post::STATUS_SCHEDULED].'</span>';
                break;

            case Post::STATUS_PUBLISHED:
                return '<span class="badge bg-dark rounded-pill">'.$labels[Post::STATUS_PUBLISHED].'</span>';
                break;
            
            default:
                return '<span class="badge bg-info rounded-pill">Brouillon</span>';
                break;
        }
    }

    public function getCommentStatus(?string $status)
    {
        $labels = Comment::getLabels();
        switch ($status) {
            case Comment::STATUS_PENDING:
                return '<span class="badge bg-secondary rounded-pill">'.$labels[Comment::STATUS_PENDING].'</span>';
                break;

            case Comment::STATUS_VALIDATED:
                return '<span class="badge bg-success rounded-pill">'.$labels[Comment::STATUS_VALIDATED].'</span>';
                break;
            
            case Comment::STATUS_ARCHIVED:
                return '<span class="badge bg-danger rounded-pill">'.$labels[Comment::STATUS_ARCHIVED].'</span>';
                break;
            
            default:
                return '<span class="badge bg-secondary rounded-pill">'.$labels[Comment::STATUS_PENDING].'</span>';
                break;
        }
    }
}
