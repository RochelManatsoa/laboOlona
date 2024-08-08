<?php

namespace App\Manager;

use Twig\Environment as Twig;
use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class OlonaTalentsManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack
    ){}

    public function getParams(): array
    {
        $params = [];
        $params['top_candidats'] = $this->em->getRepository(CandidateProfile::class)->findTopRanked();
        $params['top_annonces'] = $this->em->getRepository(JobListing::class)->findFeaturedJobListing();
        $params['top_entreprises'] = $this->em->getRepository(EntrepriseProfile::class)->findTopRanked();

        return $params;
    }


    public function getParamsJoblisting(int $from, int $size, string $query): array
    {
        return [
            'index' => 'joblisting_index',
            'body'  => [
                'from' => $from,
                'size' => $size,
                'query' => [
                    'multi_match' => [
                        'query'  => $query,
                        'fields' => [
                            'titre', 
                            'cleanDescription', 
                            'lieu', 
                            'shortDescription', 
                            'typeContrat', 
                            'budgetAnnonce', 
                            'competences.nom', 
                            'secteur.nom', 
                            'langues.nom'
                        ],
                        'fuzziness' => 'AUTO',
                    ],
                ],
                'highlight' => [
                    'fields' => [
                        'titre' => new \stdClass(),
                        'cleanDescription' => new \stdClass(),
                        'lieu' => new \stdClass(),
                        'shortDescription' => new \stdClass(),
                        'typeContrat' => new \stdClass(),
                        'budgetAnnonce' => new \stdClass(),
                        'metaDescription' => new \stdClass(),
                        'traductionEn' => new \stdClass(),
                        'competences' => new \stdClass(),
                        'secteur' => new \stdClass(),
                        'langues' => new \stdClass(),
                    ],
                    'pre_tags' => ['<strong>'],
                    'post_tags' => ['</strong>']
                ]
            ],
        ];
    }
}