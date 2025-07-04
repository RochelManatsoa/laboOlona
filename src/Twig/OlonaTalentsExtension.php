<?php

namespace App\Twig;

use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\Entreprise\Favoris;
use App\Entity\Entreprise\JobListing;
use Symfony\Component\Intl\Countries;
use Twig\Extension\AbstractExtension;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Collection;
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
            new TwigFilter('displayAge', [$this, 'displayAge']),
            new TwigFilter('stripDivP', [$this, 'stripDivP'], ['is_safe' => ['html']]),
            new TwigFilter('getFirstCommonSecteur', [$this, 'getFirstCommonSecteur']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('highlightKeywordsEntreprise', [$this, 'highlightKeywordsEntreprise']),
            new TwigFunction('highlightKeywordsAnnonce', [$this, 'highlightKeywordsAnnonce']),
            new TwigFunction('generatePseudoById', [$this, 'generatePseudoById']),
            new TwigFunction('isLikedByRecruiter', [$this, 'isLikedByRecruiter']),
        ];
    }
    
    public function countryName($countryCode)
    {
        return Countries::getName($countryCode);
    }
    
    public function displayAge(CandidateProfile $candidateProfile)
    {
        $now = new \DateTime();
        $age = 'Non renseigné';
        if ($candidateProfile->getBirthday() !== null) {
            $age = $now->diff($candidateProfile->getBirthday())->y;
        }

        return $age;
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


    public function isLikedByRecruiter(EntrepriseProfile $recruiter, int $id):bool
    {
        $candidat = $this->em->getRepository(CandidateProfile::class)->find($id);
        if($candidat){
            $liked = $this->em->getRepository(Favoris::class)->findOneBy([
                'entreprise' => $recruiter,
                'candidat' => $candidat
            ]);
            if($liked){
                return true;
            }
            return false;
        }

        return false;
    }

    public function stripDivP(string $content): string
    {
        // Étape 1: Fermez correctement les balises <strong> ouvertes sans correspondance
        // Rechercher les balises <strong> sans balise fermante correspondante dans l'ordre et les supprimer
        while (preg_match('#<strong\b[^>]*>(?![^<]*</strong>)#i', $content)) {
            $content = preg_replace('#<strong\b[^>]*>(?![^<]*</strong>)#i', '', $content);
        }

        // Étape 2: Supprime toutes les balises sauf <strong> correctement fermées
        // Remplace toutes les balises autres que <strong> et </strong>
        $content = preg_replace('#<(?!\/?strong\b)[^>]*>#i', '', $content);

        return $content;
    }

    public function getFirstCommonSecteur($secteurs1, $secteurs2)
    {
        $secteurs1 = $secteurs1 instanceof Collection ? $secteurs1->toArray() : $secteurs1;
        $secteurs2 = $secteurs2 instanceof Collection ? $secteurs2->toArray() : $secteurs2;
        $common = array_intersect($secteurs1, $secteurs2);
        return !empty($common) ? reset($common) : (empty($secteurs1) ? "Aucun secteur" : $secteurs1[0]);
    }

}