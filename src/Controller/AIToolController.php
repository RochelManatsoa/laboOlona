<?php

namespace App\Controller;

use App\Entity\AffiliateTool;
use App\Entity\AffiliateTool\Category;
use App\Entity\AffiliateTool\Tag;
use App\Form\Search\AffiliateTool\AdvancedToolSearchType;
use App\Form\Search\AffiliateTool\ToolSearchType;
use App\Manager\AffiliateToolManager;
use App\Repository\AffiliateToolRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AIToolController extends AbstractController
{
    public function __construct(
        private AffiliateToolRepository $affiliateToolRepository,
        private AffiliateToolManager $affiliateToolManager,
    ){
    }

    #[Route('/ai-tools', name: 'app_ai_tools')]
    public function index(Request $request): Response
    {
        $offset = $request->query->get('offset', 0);
        $form = $this->createForm(AdvancedToolSearchType::class);
        $formSearch = $this->createForm(ToolSearchType::class);
        $form->handleRequest($request);
        $formSearch->handleRequest($request);
        $offset = $request->query->get('offset', 0);
        $aicores = $this->affiliateToolRepository->findSearch('publish', 12, $offset);
        if ($formSearch->isSubmitted() && $formSearch->isValid()) {
            $nom = $formSearch->get('nom')->getData();
            $aicores = $this->affiliateToolManager->findSearchTools($nom);
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->get('category')->getData();
            $tag = $form->get('pricing')->getData();
            $data = $this->affiliateToolManager->advancedSearchTools($category, $tag);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return $this->json([
                    'content' => $this->renderView('ai_tool/_tools.html.twig', [
                        'aiTools' => $data
                    ])
                ], 200);
            }
        }

        return $this->render('ai_tool/index.html.twig', [
            'aiTools' => $aicores,
            'form' => $form->createView(),
            'formSearch' => $formSearch->createView(),
        ]);
    }

    #[Route('/ai-tools/{slug}', name: 'app_ai_tools_category')]
    public function category(Request $request, Category $category): Response
    {
        $offset = $request->query->get('offset', 0);
        $aicores = $this->affiliateToolRepository->findByCategory($category->getSlug(), 12, $offset);

        return $this->render('ai_tool/category.html.twig', [
            'aiTools' => $aicores,
            'category' => $category,
        ]);
    }

    #[Route('/ai-tools/{slug}/tag', name: 'app_ai_tools_tag')]
    public function tag(Request $request, Tag $tag): Response
    {
        $offset = $request->query->get('offset', 0);
        $aicores = $this->affiliateToolRepository->findByTag($tag->getSlug(), 12, $offset);

        return $this->render('ai_tool/tag.html.twig', [
            'aiTools' => $aicores,
            'tag' => $tag,
        ]);
    }

    #[Route('/ai-tool/{slug}', name: 'app_ai_tools_view')]
    public function view(Request $request, AffiliateTool $tool, AffiliateToolRepository $affiliateToolRepository): Response
    {
        $tools = $tool->getRelatedIds();
        foreach ($tools as $key => $value) {
            $relateds[] = $affiliateToolRepository->findOneBy(['customId' => $value]); 
        }
        return $this->render('ai_tool/view.html.twig', [
            'aiTool' => $tool,
            'relateds' => $relateds,
        ]);
    }

    #[Route('/filter/ai-tools', name: 'app_ai_tools_filter')]
    public function filter(Request $request, AffiliateTool $tool): Response
    {

        return $this->json([
            'content' => $this->renderView('ai_tool/_tools.html.twig', [
                'aiTools' => $data
            ])
        ], 200, []);
    }
}
