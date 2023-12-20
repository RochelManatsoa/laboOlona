<?php

namespace App\Manager;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Repository\CandidateProfileRepository;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\EntrepriseProfileRepository;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;

class EntrepriseManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private JobListingRepository $jobListingRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private UserService $userService
    ){}
    
    public function findValidCandidats(): array
    {
        return $this->candidateProfileRepository->findBy(
            ['status' => CandidateProfile::STATUS_VALID],
            ['id' => 'DESC']
        );
    }

    public function findAllAnnonces(?string $titre = null, ?string $status = null, ?string $typeContrat = null, ?string $salaire = null): array
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $qb = $this->em->createQueryBuilder();

        $parameters = [
            'entreprise' => $user->getEntrepriseProfile(),
        ];
        $conditions = [];

        if($titre == null && $status == null && $typeContrat == null && $salaire == null){
            return $this->jobListingRepository->findBy(
                ['entreprise' => $user->getEntrepriseProfile()],
                ['id' => 'DESC']
            );
        }

        if (!empty($titre)) {
            $conditions[] = '(j.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($typeContrat) ) {
            $conditions[] = '(t.nom LIKE :typeContrat )';
            $parameters['typeContrat'] = '%' . $typeContrat . '%';
        }

        if (!empty($salaire)) {
            $conditions[] = '(j.salaire LIKE :salaire )';
            $parameters['salaire'] = '%' . $salaire . '%';
        }

        if (!empty($status)) {
            $conditions[] = '(j.status LIKE :status )';
            $parameters['status'] = '%' . $status . '%';
        }

        $qb->select('j')
            ->from('App\Entity\Entreprise\JobListing', 'j')
            ->leftJoin('j.typeContrat', 't')
            ->where(implode(' AND ', $conditions))
            ->andWhere('j.entreprise = :entreprise')
            ->orderBy('j.id', 'DESC')
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }

    public function findAllCandidats(?string $titre = null, ?string $nom = null, ?string $competences = null, ?string $langues = null, ?string $availability = null): array
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $qb = $this->em->createQueryBuilder();

        $parameters = [];

        $conditions = [];

        if($titre == null && $nom == null && $competences == null && $langues == null && $availability == null){
            return $this->findValidCandidats();
        }

        if (!empty($titre)) {
            $conditions[] = '(c.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($nom) ) {
            $conditions[] = '(u.nom LIKE :nom OR u.prenom LIKE :nom OR u.email LIKE :nom )';
            $parameters['nom'] = '%' . $nom . '%';
        }

        if (!empty($competences)) {
            $conditions[] = '(s.nom LIKE :competences )';
            $parameters['competences'] = '%' . $competences . '%';
        }

        if (!empty($langues)) {
            $conditions[] = '(lg.nom LIKE :langues )';
            $parameters['langues'] = '%' . $langues . '%';
        }

        if (!empty($availability)) {
            $conditions[] = '(a.nom LIKE :availability )';
            $parameters['availability'] = '%' . $availability . '%';
        }

        $qb->select('c')
            ->from('App\Entity\CandidateProfile', 'c')
            ->leftJoin('c.competences', 's')
            ->leftJoin('c.langages', 'l')
            ->leftJoin('c.availability', 'a')
            ->leftJoin('l.langue', 'lg')
            ->leftJoin('c.candidat', 'u')
            ->where(implode(' AND ', $conditions))
            ->orderBy('c.id', 'DESC')
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }

    public function findAllCandidature(?string $titre = null, ?string $candidat = null, ?string $status = null): array
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $qb = $this->em->createQueryBuilder();

        $parameters = [
            'entreprise' => $user->getEntrepriseProfile(),
        ];

        $conditions = [];

        if($titre == null && $status == null && $candidat == null){
            return $user->getEntrepriseProfile()->getAllApplications();
        }

        if (!empty($titre)) {
            $conditions[] = '(j.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($candidat)) {
            $conditions[] = '(c.nom LIKE :candidat OR c.prenom LIKE :candidat OR c.email LIKE :candidat )';
            $parameters['candidat'] = '%' . $candidat . '%';
        }

        if (!empty($status)) {
            $conditions[] = '(a.status LIKE :status )';
            $parameters['status'] = '%' . $status . '%';
        }

        $qb->select('a')
            ->from('App\Entity\Candidate\Applications', 'a')
            ->leftJoin('a.annonce', 'j')
            ->leftJoin('a.candidat', 'c')
            ->where(implode(' AND ', $conditions))
            ->andWhere('j.entreprise = :entreprise')
            ->orderBy('a.id', 'DESC')
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }
}
