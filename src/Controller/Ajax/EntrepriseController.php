<?php

namespace App\Controller\Ajax;

use App\Entity\Secteur;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\LangueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Candidate\CompetencesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EntrepriseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateProfileRepository $candidateProfileRepository,
        private CompetencesRepository $competencesRepository,
        private LangueRepository $langueRepository,
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

}
