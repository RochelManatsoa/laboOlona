<?php

namespace App\Controller;

use App\Entity\AffiliateTool;
use App\Service\OpenAITranslator;
use App\Twig\AppExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TranslationController extends AbstractController
{

    public function __construct(
        private OpenAITranslator $translator,
        private AppExtension $appExtension,
    ){
    }

    #[Route('/translate/{id}', name: 'app_translate')]
    public function translate(AffiliateTool $tool): Response
    {
        return new Response($this->translator->translate(
            $this->appExtension->filterContent($this->appExtension->doShortcode($tool->getDescription())) ,
                'en',
                'fr'
        ));
    }
}
