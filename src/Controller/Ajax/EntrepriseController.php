<?php

namespace App\Controller\Ajax;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\Favoris;
use App\Entity\EntrepriseProfile;
use App\Entity\Secteur;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\LangueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Candidate\CompetencesRepository;
use App\Repository\Entreprise\FavorisRepository;
use App\Service\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EntrepriseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateProfileRepository $candidateProfileRepository,
        private CompetencesRepository $competencesRepository,
        private LangueRepository $langueRepository,
        private FavorisRepository $favorisRepository,
        private UserService $userService,
    ) {
    }
    
    #[Route('/ajax/get-titre-poste', name: 'get_titres_poste')]
    public function getTitres(Request $request)
    {
        $secteursString = $request->query->get('secteurs');
        if ($secteursString) {
            // Diviser la chaîne en un tableau en utilisant ',' comme délimiteur
            $secteurs = explode(',', $secteursString);
            
            // Vous pouvez également supprimer les espaces autour de chaque secteur
            $secteurs = array_map('trim', $secteurs);
        
            // Maintenant, $secteurs est un tableau contenant les secteurs
            
            // Récupérer les titres basés sur les secteurs
            $titres = $this->candidateProfileRepository->findUniqueTitlesBySecteurs($secteurs);
            $competences = $this->competencesRepository->findCompetencesBySecteurs($secteurs);
            // Récupérer les titres basés sur les secteurs
    
            // Renvoyer les options de titre sous forme de réponse JSON
            return $this->json([
                'titres' => array_map(function($titre) { return ['id' => $titre['id'], 'nom' => $titre['titre']]; }, $titres),
                'competences' => array_map(function($comp) { return ['id' => $comp['id'], 'nom' => $comp['nom']]; }, $competences),
            ]);
        }
        return $this->json([
            'titre' => [],
            'competences' => []
        ], 200, []);

    }
    
    #[Route('/ajax/get-competence-poste', name: 'get_competences_poste')]
    public function getCompetences(Request $request)
    {
        $secteursString = $request->query->get('secteurs');
        if ($secteursString) {
            // Diviser la chaîne en un tableau en utilisant ',' comme délimiteur
            $secteurs = explode(',', $secteursString);
            
            // Vous pouvez également supprimer les espaces autour de chaque secteur
            $secteurs = array_map('trim', $secteurs);
        
            // Maintenant, $secteurs est un tableau contenant les secteurs
            
            // Récupérer les competences basés sur les secteurs
            $competences = $this->competencesRepository->findCompetencesBySecteurs($secteurs);
        }

        // Récupérer les competences basés sur les secteurs

        // Renvoyer les options de titre sous forme de réponse JSON
        return $this->json([
            'competences' => $competences
        ], 200, [], ['groups' => 'identity']);
    }
    
    #[Route('/favori/ajouter/{id}', name: 'ajouter_favori')]
    public function ajouterFavori(Request $request, CandidateProfile $candidat)
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        if(!$entreprise instanceof EntrepriseProfile){
            return $this->json(['error' => 'Profil entreprise non trouvé'], Response::HTTP_FORBIDDEN);
        }
        $favoris = $this->favorisRepository->findOneBy([
            'entreprise' => $entreprise,
            'candidat' => $candidat
        ]);
        if($favoris){
            return $this->json(['message' => 'Ce candidat est déjà dans vos favoris'], Response::HTTP_OK);
        }

        $favori = new Favoris();
        $favori->setEntreprise($entreprise);
        $favori->setCandidat($candidat);
    
        // Persiste le nouveau favori dans la base de données
        $this->em->persist($favori);
        $this->em->flush();
    
        // Renvoie une réponse de succès
        return $this->json([
            'message' => 'Candidat ajouté aux favoris avec succès'
        ], Response::HTTP_CREATED);
    }
    
    #[Route('/favori/supprimer/{id}', name: 'supprimer_favori')]
    public function supprimerFavori(Request $request, CandidateProfile $candidat, EntityManagerInterface $em)
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        if (!$entreprise instanceof EntrepriseProfile) {
            return $this->json(['error' => 'Profil entreprise non trouvé'], Response::HTTP_FORBIDDEN);
        }

        $favori = $this->favorisRepository->findOneBy([
            'entreprise' => $entreprise,
            'candidat' => $candidat
        ]);

        $em->remove($favori);
        $em->flush();

        return $this->json([
            'message' => 'Candidat retiré des favoris avec succès'
        ], Response::HTTP_OK);
    }

}
