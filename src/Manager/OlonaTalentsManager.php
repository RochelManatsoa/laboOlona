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
                        // 'fuzziness' => 'AUTO',
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
    
    public function getParamsPremiumJoblisting(int $from, int $size, string $query): array
    {
        return [
            'index' => 'joblisting_premium_index',
            'body'  => [
                'from' => $from,
                'size' => $size,
                'query' => [
                    'multi_match' => [
                        'query'  => $query,
                        'fields' => [
                            'titre', 'description', 'lieu', 'shortDescription', 'typeContrat', 'budgetAnnonce', 
                            'competences.nom', 'secteur.nom', 'langues.nom'
                        ],
                        // 'fuzziness' => 'AUTO',
                    ],
                ],
            ],
        ];
    }

    public function getParamsCandidates(int $from, int $size, string $query): array
    {
        return [
            'index' => 'candidate_profile_index',
            'body'  => [
                'from' => $from,
                'size' => $size,
                'query' => [
                    'multi_match' => [
                        'query'  => $query,
                        'fields' => [
                            'titre', 
                            'resume', 
                            'localisation', 
                            'technologies', 
                            'tools', 
                            'resultFree', 
                            'metaDescription', 
                            'traductionEn', 
                            'competences.nom', 
                            'experiences.titre', 
                            'experiences.description',
                            'secteurs.nom', 
                            'langages.nom'
                        ],
                        // 'fuzziness' => 'AUTO',
                    ],
                ],
                'highlight' => [
                    'fields' => [
                        'titre' => new \stdClass(),
                        'resume' => new \stdClass(),
                        'localisation' => new \stdClass(),
                        'technologies' => new \stdClass(),
                        'tools' => new \stdClass(),
                        'resultFree' => new \stdClass(),
                        'metaDescription' => new \stdClass(),
                        'traductionEn' => new \stdClass(),
                        'competences' => new \stdClass(),
                        'experiences' => new \stdClass(),
                        'secteurs' => new \stdClass(),
                        'langages' => new \stdClass(),
                    ],
                    'pre_tags' => ['<strong>'],
                    'post_tags' => ['</strong>']
                ]
            ],
        ];
    }
    
    public function getParamsPremiumCandidates(int $from, int $size, string $query): array
    {
        return [
            'index' => 'candidate_premium_index',
            'body'  => [
                'from' => $from,
                'size' => $size,
                'query' => [
                    'multi_match' => [
                        'query'  => $query,
                        'fields' => [
                            'titre', 'resume', 'localisation', 'technologies', 'tools', 'badKeywords', 'resultFree', 'metaDescription', 'traductionEn', 
                            'competences.nom', 'experiences.titre', 'experiences.description','secteurs.nom', 'langages.nom'
                        ],
                        // 'fuzziness' => 'AUTO',
                    ],
                ],
            ],
        ];
    }

    public function getParamsPrestations(int $from, int $size, string $query): array
    {
        return [
            'index' => 'prestation_index',
            'body'  => [
                'from' => $from,
                'size' => $size,
                'query' => [
                    'multi_match' => [
                        'query'  => $query,
                        'fields' => [
                            'titre',
                            'cleanDescription',
                            'competencesRequises.nom',
                            'tarifsProposes',
                            'modalitesPrestation',
                            'specialisations.nom',
                            'evaluations.note',
                            'disponibilites',
                            'createdAt',
                            'openai',
                        ],
                        // 'fuzziness' => 'AUTO',
                    ],
                ],
                'highlight' => [
                    'fields' => [
                        'titre'                 => new \stdClass(),
                        'cleanDescription'      => new \stdClass(),
                        'competencesRequises'   => new \stdClass(),
                        'tarifsProposes'        => new \stdClass(),
                        'modalitesPrestation'   => new \stdClass(),
                        'specialisations'       => new \stdClass(),
                        'createdAt'             => new \stdClass(),
                        'openai'                => new \stdClass(),
                    ],
                    'pre_tags' => ['<strong>'],
                    'post_tags' => ['</strong>']
                ]
            ],
        ];
    }
    
    public function getParamsPremiumPrestations(int $from, int $size, string $query): array
    {
        return [
            'index' => 'prestation_premium_index',
            'body'  => [
                'from' => $from,
                'size' => $size,
                'query' => [
                    'multi_match' => [
                        'query'  => $query,
                        'fields' => [
                            'titre',
                            'cleanDescription',
                            'competencesRequises.nom',
                            'tarifsProposes',
                            'modalitesPrestation',
                            'specialisations.nom',
                            'evaluations.note',
                            'disponibilites',
                            'createdAt',
                            'openai',
                        ],
                        // 'fuzziness' => 'AUTO',
                    ],
                ],
                'highlight' => [
                    'fields' => [
                        'titre'                 => new \stdClass(),
                        'cleanDescription'      => new \stdClass(),
                        'competencesRequises'   => new \stdClass(),
                        'tarifsProposes'        => new \stdClass(),
                        'modalitesPrestation'   => new \stdClass(),
                        'specialisations'       => new \stdClass(),
                        'createdAt'             => new \stdClass(),
                        'openai'                => new \stdClass(),
                    ],
                    'pre_tags' => ['<strong>'],
                    'post_tags' => ['</strong>']
                ]
            ],
        ];
    }
}