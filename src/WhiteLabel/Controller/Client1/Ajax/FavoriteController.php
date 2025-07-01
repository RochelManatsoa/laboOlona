<?php

namespace App\WhiteLabel\Controller\Client1\Ajax;

use App\WhiteLabel\Entity\Client1\CandidateProfile;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use App\WhiteLabel\Entity\Client1\Entreprise\Favoris;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use App\WhiteLabel\Repository\Client1\Entreprise\FavorisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajax')]
class FavoriteController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(private ManagerRegistry $managerRegistry)
    {
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    #[Route('/favorite/add/candidate/{id}', name: 'app_white_label_client1_favorite_add_candidate', methods: ['POST'])]
    public function addCandidate(Request $request, int $id, FavorisRepository $favorisRepository): Response
    {
        /** @var \App\WhiteLabel\Entity\Client1\User $user */
        $user = $this->getUser();
        $entreprise = $user?->getEntrepriseProfile();

        if (!$entreprise instanceof EntrepriseProfile) {
            return $this->json(['error' => 'Profil entreprise non trouvé'], Response::HTTP_FORBIDDEN);
        }

        $candidat = $this->entityManager->getRepository(CandidateProfile::class)->find($id);
        if (!$candidat) {
            return $this->json(['error' => 'Candidat introuvable'], Response::HTTP_BAD_REQUEST);
        }

        $criteria = [
            'entreprise' => $entreprise,
            'candidat' => $candidat,
        ];
        if ($request->query->get('annonce')) {
            $criteria['annonce'] = $request->query->get('annonce');
        }

        if ($favorisRepository->findOneBy($criteria)) {
            return $this->json(['message' => 'Ce candidat est déjà dans vos favoris'], Response::HTTP_OK);
        }

        $favori = new Favoris();
        $favori->setEntreprise($entreprise);
        $favori->setCandidat($candidat);
        $favori->setCreatedAt(new \DateTime());

        if ($request->query->get('annonce')) {
            $annonce = $this->entityManager->getRepository(JobListing::class)->find($request->query->get('annonce'));
            if ($annonce) {
                $favori->setAnnonce($annonce);
            }
        }

        $this->entityManager->persist($favori);
        $this->entityManager->flush();

        return $this->json(['message' => 'Candidat ajouté aux favoris avec succès'], Response::HTTP_CREATED);
    }

    #[Route('/favorite/delete/candidate/{id}', name: 'app_white_label_client1_favorite_delete_candidate', methods: ['POST'])]
    public function deleteCandidate(Request $request, int $id, FavorisRepository $favorisRepository): Response
    {
        /** @var \App\WhiteLabel\Entity\Client1\User $user */
        $user = $this->getUser();
        $entreprise = $user?->getEntrepriseProfile();

        if (!$entreprise instanceof EntrepriseProfile) {
            return $this->json(['error' => 'Profil entreprise non trouvé'], Response::HTTP_FORBIDDEN);
        }

        $candidat = $this->entityManager->getRepository(CandidateProfile::class)->find($id);
        if (!$candidat) {
            return $this->json(['error' => 'Candidat introuvable'], Response::HTTP_BAD_REQUEST);
        }

        $criteria = [
            'entreprise' => $entreprise,
            'candidat' => $candidat,
        ];
        if ($request->query->get('annonce')) {
            $criteria['annonce'] = $request->query->get('annonce');
        }
        $favori = $favorisRepository->findOneBy($criteria);

        if ($favori) {
            $this->entityManager->remove($favori);
            $this->entityManager->flush();
        }

        return $this->json(['message' => 'Candidat retiré des favoris avec succès'], Response::HTTP_OK);
    }
}
