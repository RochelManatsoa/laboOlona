<?php

namespace App\Manager;

use Twig\Environment as Twig;
use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use App\Entity\Prestation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\ElasticsearchService;

class OlonaTalentsManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private ElasticsearchService $elasticsearch,
        private PaginatorInterface $paginatorInterface
    ){}

    public function getParams(): array
    {
        $params = [];
        $params['top_candidats'] = $this->em->getRepository(CandidateProfile::class)->findTopRanked();
        $params['top_annonces'] = $this->em->getRepository(JobListing::class)->findFeaturedJobListing();
        $params['top_entreprises'] = $this->em->getRepository(EntrepriseProfile::class)->findTopRanked();

        return $params;
    }

    public function searchEntities(string $type, int $from, int $size, string $query): array
    {
        $paramsSearch = $this->getSearchParams($type, $from, $size, $query);
        $searchResults = $this->elasticsearch->search($paramsSearch);

        $ids = array_map(fn($hit) => $hit['_id'], $searchResults['hits']['hits']);
        $repository = $this->getRepositoryForType($type);
        $entities = $repository->findBy(['id' => $ids]);
        usort($entities, function ($a, $b) use ($ids) {
            return array_search($a->getId(), $ids) <=> array_search($b->getId(), $ids);
        });

        return [
            'entities' => $entities,
            'totalResults' => $searchResults['hits']['total']['value'],
            'hasMore' => count($searchResults['hits']['hits']) === $size
        ];
    }

    public function getBoostedEntities(string $type, int $from, int $size, string $query): array
    {
        $paramsBoost = $this->getBoostParams($type, 0, 10000, $query);
        $boostResults = $this->elasticsearch->search($paramsBoost);

        $boostIds = array_map(fn($hit) => $hit['_id'], $boostResults['hits']['hits']);
        $repository = $this->getRepositoryForType($type);
        $boostEntities = $repository->findBy(['id' => $boostIds]);
        usort($boostEntities, function ($a, $b) use ($boostIds) {
            return array_search($a->getId(), $boostIds) <=> array_search($b->getId(), $boostIds);
        });

        if (count($boostEntities) > 0) {
            $boostFrom = $from % count($boostEntities);
        } else {
            $boostFrom = 0; 
        }

        return array_slice($boostEntities, $boostFrom, $size);
    }

    private function getSearchParams(string $type, int $from, int $size, string $query): array
    {
        return match ($type) {
            'candidates' => $this->getParamsCandidates($from, $size, $query),
            'joblistings' => $this->getParamsJoblisting($from, $size, $query),
            'prestations' => $this->getParamsPrestations($from, $size, $query),
            default => throw new \InvalidArgumentException("Invalid type: $type"),
        };
    }

    private function getBoostParams(string $type, int $from, int $size, string $query): array
    {
        return match ($type) {
            'candidates' => $this->getParamsPremiumCandidates($from, $size, $query),
            'joblistings' => $this->getParamsPremiumJoblisting($from, $size, $query),
            'prestations' => $this->getParamsPremiumPrestations($from, $size, $query),
            default => throw new \InvalidArgumentException("Invalid type: $type"),
        };
    }

    private function getRepositoryForType(string $type)
    {
        return match ($type) {
            'candidates' => $this->em->getRepository(CandidateProfile::class),
            'joblistings' => $this->em->getRepository(JobListing::class),
            'prestations' => $this->em->getRepository(Prestation::class),
            default => throw new \InvalidArgumentException("Invalid type: $type"),
        };
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
                        'fuzziness' => 'AUTO',
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
                        'fuzziness' => 'AUTO',
                    ],
                ],
            ],
        ];
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
                        'fuzziness' => 'AUTO',
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
                        'fuzziness' => 'AUTO',
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
                        'fuzziness' => 'AUTO',
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
