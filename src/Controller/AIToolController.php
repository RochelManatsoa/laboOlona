<?php

namespace App\Controller;

use App\Entity\AffiliateTool;
use App\Entity\AffiliateTool\Category;
use App\Entity\AffiliateTool\Tag;
use App\Manager\AffiliateToolManager;
use App\Repository\AffiliateToolRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AIToolController extends AbstractController
{
    public function __construct(
        private AffiliateToolRepository $affiliateToolRepository,
        private AffiliateToolManager $affiliateToolManager,
    ){}

    #[Route('/ai-tools', name: 'app_ai_tools')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_v2_dashboard_ai_tools');
    }

    #[Route('/ai-tools/{slug}', name: 'app_ai_tools_category')]
    public function category(Category $category): Response
    {
        return $this->redirectToRoute('app_v2_dashboard_ai_tool_category', ['slug' => $category->getSlug()]);
    }

    #[Route('/ai-tools/{slug}/tag', name: 'app_ai_tools_tag')]
    public function tag(Tag $tag): Response
    {
        return $this->redirectToRoute('app_v2_dashboard_ai_tool_tag', ['slug' => $tag->getSlug()]);
    }

    #[Route('/ai-tool/{slug}', name: 'app_ai_tools_view')]
    public function view(AffiliateTool $tool): Response
    {
        return $this->redirectToRoute('app_v2_dashboard_ai_tool_view', ['slug' => $tool->getSlug()]);
    }

    #[Route('/filter/ai-tools', name: 'app_ai_tools_filter')]
    public function filter(): Response
    {
        return $this->redirectToRoute('app_v2_dashboard_ai_tools');
    }
}
