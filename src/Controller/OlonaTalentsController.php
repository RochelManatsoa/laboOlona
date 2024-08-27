<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Security\EmailVerifier;
use App\Entity\CandidateProfile;
use App\Form\RegistrationFormType;
use Symfony\Component\Mime\Address;
use App\Manager\OlonaTalentsManager;
use App\Service\ElasticsearchService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class OlonaTalentsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateProfileRepository $candidatRepository,
        private JobListingRepository $annonceRepository,
        private ElasticsearchService $elasticsearch,
        private OlonaTalentsManager $olonaTalentsManager,
        private PaginatorInterface $paginatorInterface,
        private Security $security,
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('v2/home.html.twig', [
            'candidats' => $this->candidatRepository->findBy(
                ['status' => CandidateProfile::STATUS_VALID],
                ['id' => 'DESC'],
                18
            ),
        ]);
    }

    #[Route('/upgrade', name: 'app_olona_talents_upgrade')]
    public function upgrade(): Response
    {
        return $this->render('v2/upgrade.html.twig', []);
    }

    #[Route('/premium', name: 'app_olona_talents_premium')]
    public function premium(): Response
    {
        return $this->render('v2/premium.html.twig', []);
    }

    #[Route('/v2/olona-register', name: 'app_olona_talents_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EmailVerifier $emailVerifier): Response
    {
        $user = new User();
        $user->setDateInscription(new DateTime());
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $this->em->persist($user);
            $this->em->flush();

            $emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('support@olona-talents.com', 'Olona Talents'))
                    ->to($user->getEmail())
                    ->subject('Veuillez confirmer votre inscription sur olona-talents.com')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
                    ->context(['user' => $user])
            );

            return $this->redirectToRoute('app_email_sending');
        }

        return $this->render('v2/olona_register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/result', name: 'app_olona_talents_result')]
    public function result(Request $request): Response
    {
        $query = $request->query->get('q');
        $page = $request->query->getInt('page', 1);
        $size = $request->query->getInt('size', 10);
        $from = ($page - 1) * $size;
        $params = [];
        $currentUser = $this->security->getUser();
        if($currentUser instanceof User){
            $params['type'] = $currentUser->getType();
        }
        $params['currentPage'] = $page;
        $params['size'] = $size;
        $params['searchQuery'] = $query;

        $paramsCandidate = [
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
        $paramsCandidatePremium = [
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

        $paramsJoblisting = [
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
        $paramsJoblistingPremium = [
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

        $paramsPrestation = [
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

        $candidates = $this->elasticsearch->search($paramsCandidate);
        $totalCandidatesResults = $candidates['hits']['total']['value'];
        $totalPages = ceil($totalCandidatesResults / $size);
        $params['totalPages'] = $totalPages;
        $params['candidats'] = $candidates['hits']['hits'];
        $params['totalCandidatesResults'] = $totalCandidatesResults;
        
        $premiums = $this->elasticsearch->search($paramsCandidatePremium);
        $params['top_candidats'] = $this->paginatorInterface->paginate(
            $premiums['hits']['hits'], 
            $page, 
            8
        );

        $joblistings = $this->elasticsearch->search($paramsJoblisting);
        $totalJobListingsResults = $joblistings['hits']['total']['value'];
        $totalAnnoncesPages = ceil($totalJobListingsResults / $size);
        $params['totalAnnoncesPages'] = $totalAnnoncesPages;
        $params['annonces'] = $joblistings['hits']['hits'];
        $params['totalJobListingsResults'] = $totalJobListingsResults;
        
        $premiumJoblistings = $this->elasticsearch->search($paramsJoblistingPremium);
        $params['top_annonces'] = $this->paginatorInterface->paginate(
            $premiumJoblistings['hits']['hits'], 
            $page, 
            8
        );
        
        $prestations = $this->elasticsearch->search($paramsPrestation);
        $params['prestations'] = $prestations['hits']['hits'];
        $totalPrestationsResults = $prestations['hits']['total']['value'];
        $totalPrestationsPages = ceil($totalPrestationsResults / $size);
        $params['totalPrestationsPages'] = $totalPrestationsPages;
        $params['totalPrestationsResults'] = $totalPrestationsResults;

        $params['entreprises'] = [];
        $params['top_entreprises'] = [];

        // dd($params);
        // dd($candidates, $joblistings, $entreprises);
        if($currentUser){
            return $this->render('v2/dashboard/result.html.twig', $params);
        }

        return $this->render('v2/result.html.twig', $params);
    }
}
