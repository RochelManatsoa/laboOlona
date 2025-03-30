<?php

namespace App\Service\User;

use DateTime;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\{CandidateProfile, EntrepriseProfile, ModerateurProfile, User};
use App\Repository\{CandidateProfileRepository, EntrepriseProfileRepository, UserRepository};

class UserService
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private RequestStack $requestStack,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $encoder,
    ){}

    public function getCurrentUser()
    {
        return $this->security->getUser();
    }

    public function checkProfile()
    {
        /** @var User $user */
        $user = $this->getCurrentUser();
        switch ($user->getType()) {
            case User::ACCOUNT_REFERRER :
                $profile = $user->getReferrerProfile();
                break;

            case User::ACCOUNT_CANDIDAT :
                $profile = $user->getCandidateProfile();
                break;

            case User::ACCOUNT_ENTREPRISE :
                $profile = $user->getEntrepriseProfile();
                break;

            case User::ACCOUNT_MODERATEUR :
                $profile = $user->getModerateurProfile();
                break;
            
            default:
                $profile = null;
                break;
        }

        return $profile;
    }

    public function checkUserProfile(User $user)
    {
        switch ($user->getType()) {
            case User::ACCOUNT_REFERRER :
                $profile = $user->getReferrerProfile();
                break;

            case User::ACCOUNT_CANDIDAT :
                $profile = $user->getCandidateProfile();
                break;

            case User::ACCOUNT_ENTREPRISE :
                $profile = $user->getEntrepriseProfile();
                break;

            case User::ACCOUNT_MODERATEUR :
                $profile = $user->getModerateurProfile();
                break;
            
            default:
                $profile = null;
                break;
        }

        return $profile;
    }

    public function getReferrer()
    {
        /** @var User $user */
        $user = $this->getCurrentUser();
        
        return $user->getReferrerProfile();
    }

    public function init()
    {
        $user = new User();
        $user->setDateInscription(new DateTime());
        
        return $user;
    }

    public function initEntreprise(User $user)
    {
        $profilEntreprise = new EntrepriseProfile();
        $profilEntreprise->setEntreprise($user);
        
        return $profilEntreprise;
    }

    public function initCandidat(User $user)
    {
        $profilCandidat = new CandidateProfile();
        $profilCandidat->setCandidat($user);
        
        return $profilCandidat;
    }

    public function save(User $user)
    {
		$this->em->persist($user);
        $this->em->flush();
    }

    public function saveEntreprise(EntrepriseProfile $profilEntreprise)
    {
		$this->em->persist($profilEntreprise);
        $this->em->flush();
    }

    public function saveCandidat(CandidateProfile $profilCandidat)
    {
		$this->em->persist($profilCandidat);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $user = $form->getData();
        $this->save($user);

        return $user;

    }

    public function saveProfilEntreprise(Form $form)
    {
        $profilEntreprise = $form->getData();
        $this->saveEntreprise($profilEntreprise);

        return $profilEntreprise;

    }

    public function saveProfilCandidat(Form $form)
    {
        $profilCandidat = $form->getData();
        $this->saveCandidat($profilCandidat);

        return $profilCandidat;

    }

    public function initUser(string $email, string $plainPassword):User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if(!$user instanceof User){
            $user = new User();
                $user->setDateInscription(new DateTime())
                ->setEmail($email)
                ->setPassword($this->encoder->hashPassword($user, $plainPassword))
            ;
        }

        $user
            ->setNom('Olona')
            ->setType(User::ACCOUNT_MODERATEUR)
            ->setPrenom('Modérateur')
        ;

        $this->em->persist($user);
        $this->em->flush();

        return $user;

    }

    public function checkUserPassword(User $user, string $plainPassword):bool
    {
        return $this->encoder->isPasswordValid($user, $plainPassword);
    }
    
    public function getRedirectRoute(User $user, Request $request): array
    {
        $profile = $this->checkProfile($user);
        $route = $request->attributes->get('_route');// récupère le paramètre 'route' de la requête, si présent
    
        $routeParams = $request->query->all(); // récupère tous les paramètres de la query string
        // dd($route,  $profile, $routeParams);
        $routes = [
            'app_v2_prestation' => [
                CandidateProfile::class => 'app_tableau_de_bord_candidat_annuaire_de_services',
                EntrepriseProfile::class => 'app_tableau_de_bord_entreprise_annuaire_de_services',
            ],
            'app_v2_view_prestation' => [
                CandidateProfile::class => ['route' => 'app_tableau_de_bord_candidat_view_prestation', 'params' => $routeParams],
                EntrepriseProfile::class => ['route' => 'app_tableau_de_bord_entreprise_view_prestation', 'params' => $routeParams],
            ],
            'app_v2_job_offer' => [
                CandidateProfile::class => 'app_tableau_de_bord_candidat_trouver_des_missions',
                EntrepriseProfile::class => 'app_tableau_de_bord_entreprise_offre_emploi',
            ],
            'app_v2_job_offer_view' => [
                CandidateProfile::class => ['route' => 'app_tableau_de_bord_candidat_view_job_offer', 'params' => $routeParams],
                EntrepriseProfile::class => ['route' => 'app_tableau_de_bord_entreprise_offre_emploi', 'params' => $routeParams],
            ],
            'app_v2_contacts' => [
                CandidateProfile::class => ['route' => 'app_tableau_de_bord_candidat_reseaux_professionnelles', 'params' => $routeParams],
                EntrepriseProfile::class => ['route' => 'app_tableau_de_bord_entreprise', 'params' => $routeParams],
            ],
            'app_v2_dashboard_notification' => [
                CandidateProfile::class => ['route' => 'app_tableau_de_bord_candidat_notification', 'params' => $routeParams],
                EntrepriseProfile::class => ['route' => 'app_tableau_de_bord_entreprise_notification', 'params' => $routeParams],
            ],
            'app_v2_user_order' => [
                CandidateProfile::class => ['route' => 'app_tableau_de_bord_candidat_mes_commandes', 'params' => $routeParams],
                EntrepriseProfile::class => ['route' => 'app_tableau_de_bord_entreprise_mes_commandes', 'params' => $routeParams],
            ],
            'app_v2_dashboard_formation' => [
                CandidateProfile::class => ['route' => 'app_tableau_de_bord_candidat', 'params' => $routeParams],
                EntrepriseProfile::class => ['route' => 'app_tableau_de_bord_entreprise', 'params' => $routeParams],
            ],
            'app_v2_credit' => [
                CandidateProfile::class => ['route' => 'app_tableau_de_bord_candidat_tarifs_standard', 'params' => $routeParams],
                EntrepriseProfile::class => ['route' => 'app_tableau_de_bord_entreprise_tarifs_standard', 'params' => $routeParams],
            ],
            'app_v2_credit_view' => [
                CandidateProfile::class => ['route' => 'app_tableau_de_bord_candidat_credit', 'params' => $routeParams],
                EntrepriseProfile::class => ['route' => 'app_tableau_de_bord_entreprise_credit', 'params' => $routeParams],
            ],
            'app_v2_contact' => [
                CandidateProfile::class => ['route' => 'app_tableau_de_bord_candidat_assistance', 'params' => $routeParams],
                EntrepriseProfile::class => ['route' => 'app_tableau_de_bord_entreprise_assistance', 'params' => $routeParams],
            ]
        ];
    
        if ($route && array_key_exists($route, $routes)) {
            $routeConfig = $routes[$route];
            
            if (array_key_exists(get_class($profile), $routeConfig)) {
                $selectedRoute = $routeConfig[get_class($profile)];
                
                return is_array($selectedRoute) ? $selectedRoute : ['route' => $selectedRoute, 'params' => []];
            }
        }
    
        // Fallback si aucune route spécifique n'est fournie ou si la classe de profil n'est pas gérée
        if ($profile instanceof CandidateProfile) {
            return ['route' => 'app_tableau_de_bord_candidat', 'params' => []];
        }
        if ($profile instanceof EntrepriseProfile || $profile instanceof ModerateurProfile) {
            return ['route' => 'app_tableau_de_bord_entreprise', 'params' => []];
        }
    
        return ['route' => 'app_v2_dashboard_create_profile', 'params' => []];
    }
}