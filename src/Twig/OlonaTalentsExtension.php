<?php

namespace App\Twig;

use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Entity\EntrepriseProfile;
use App\Entity\Entreprise\JobListing;
use Symfony\Component\Intl\Countries;
use Twig\Extension\AbstractExtension;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\ReferrerProfileRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OlonaTalentsExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private Security $security,
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urlGenerator,
        private ReferrerProfileRepository $referrerProfileRepository,
    ){}
    
    public function getFilters(): array
    {
        return [
            new TwigFilter('reffererStatusLabel', [$this, 'reffererStatusLabel']),
            new TwigFilter('countryName', [$this, 'countryName']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('highlightKeywordsEntreprise', [$this, 'highlightKeywordsEntreprise']),
            new TwigFunction('highlightKeywordsAnnonce', [$this, 'highlightKeywordsAnnonce']),
            new TwigFunction('generatePseudoById', [$this, 'generatePseudoById']),
        ];
    }
    
    public function countryName($countryCode)
    {
        return Countries::getName($countryCode);
    }
    
    public function highlightKeywordsAnnonce(int $id, string $content): string
    {
        $annonce = $this->em->getRepository(JobListing::class)->find($id);
        $sentences = explode('.', strip_tags($annonce->getShortDescription()));
        $keywords = explode(' ', $content);
        $highlightedText = ""; // Variable pour stocker la phrase avec les mots-clés mis en évidence

        foreach ($sentences as $sentence) {
            foreach ($keywords as $keyword) {
                if (stripos($sentence, $keyword) !== false) {
                    $highlightedText = $this->keywords($sentence, $keywords) . '.';
                    break 2; // Sortir de toutes les boucles une fois un mot-clé trouvé
                }
            }
        }

        if (empty($highlightedText) && !empty($sentences)) {
            // Si aucun mot-clé n'est trouvé, retourner la première phrase
            return $sentences[0] . '.';
        }

        return $highlightedText; // Retourner la première phrase avec les mots-clés mis en évidence, ou la première phrase si aucun mot-clé n'est trouvé
    }
    
    public function highlightKeywordsEntreprise(int $id, string $content): string
    {
        $annonce = $this->em->getRepository(EntrepriseProfile::class)->find($id);
        $sentences = explode('.', strip_tags($annonce->getDescription()));
        $keywords = explode(' ', $content);
        $highlightedText = ""; // Variable pour stocker la phrase avec les mots-clés mis en évidence

        foreach ($sentences as $sentence) {
            foreach ($keywords as $keyword) {
                if (stripos($sentence, $keyword) !== false) {
                    $highlightedText = $this->keywords($sentence, $keywords) . '.';
                    break 2; // Sortir de toutes les boucles une fois un mot-clé trouvé
                }
            }
        }

        if (empty($highlightedText) && !empty($sentences)) {
            // Si aucun mot-clé n'est trouvé, retourner la première phrase
            return $sentences[0] . '.';
        }

        return $highlightedText; // Retourner la première phrase avec les mots-clés mis en évidence, ou la première phrase si aucun mot-clé n'est trouvé
    }

    private function keywords($text, $keywords) {
        foreach ($keywords as $keyword) {
            $text = preg_replace('/(' . preg_quote($keyword) . ')/i', '<strong>$1</strong>', $text);
        }
        return $text;
    }

    public function generatePseudoById(int $id):string
    {
        $letters = 'OT';
        $paddedId = sprintf('%04d', $id);

        return $letters . $paddedId;
    }

}