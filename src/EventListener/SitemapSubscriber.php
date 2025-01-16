<?php

namespace App\EventListener;

use App\Repository\Blog\CategoryRepository;
use App\Repository\Blog\PostRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;

class SitemapSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PostRepository $postRepository,
        private CategoryRepository $categoryRepository,
    ){}

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            SitemapPopulateEvent::class => 'populate',
        ];
    }

    /**
     * @param SitemapPopulateEvent $event
     */
    public function populate(SitemapPopulateEvent $event): void
    {
        $this->registerBlogPostsUrls($event->getUrlContainer(), $event->getUrlGenerator());
        $this->registerCategoryUrls($event->getUrlContainer(), $event->getUrlGenerator());
    }

    /**
     * @param UrlContainerInterface $urls
     * @param UrlGeneratorInterface $router
     */
    public function registerBlogPostsUrls(UrlContainerInterface $urls, UrlGeneratorInterface $router): void
    {
        $posts = $this->postRepository->findPublished();

        foreach ($posts as $post) {
            $urls->addUrl(
                new UrlConcrete(
                    $router->generate(
                        'app_blog_view',
                        ['slug' => $post->getSlug()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ),
                'blog'
            );
        }
    }

    /**
     * Ajouter les URLs des catégories.
     *
     * @param UrlContainerInterface $urls
     * @param UrlGeneratorInterface $router
     */
    public function registerCategoryUrls(UrlContainerInterface $urls, UrlGeneratorInterface $router): void
    {
        $categories = $this->categoryRepository->findPublished();

        foreach ($categories as $category) {
            $urls->addUrl(
                new UrlConcrete(
                    $router->generate(
                        'app_blog_category',
                        ['slug' => $category->getSlug()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ),
                'category'
            );
        }
    }
}