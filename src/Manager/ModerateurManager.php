<?php

namespace App\Manager;

use App\Entity\EntrepriseProfile;
use App\Entity\Finance\Devise;
use DateTime;
use App\Entity\Langue;
use App\Entity\Secteur;
use Symfony\Component\Form\Form;
use App\Service\User\UserService;
use App\Repository\SecteurRepository;
use App\Entity\Moderateur\TypeContrat;
use App\Repository\Candidate\ApplicationsRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CandidateProfileRepository;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Moderateur\TypeContratRepository;
use App\Repository\ModerateurProfileRepository;
use App\Security\Voter\ModerateurVoter;
use Symfony\Component\String\Slugger\SluggerInterface;

class ModerateurManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private SecteurRepository $secteurRepository,
        private TypeContratRepository $typeContratRepository,
        private JobListingRepository $jobListingRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private ModerateurProfileRepository $moderateurProfileRepository,
        private ApplicationsRepository $applicationsRepository,
        private UserService $userService
    ){}

    public function initProject(): void
    {
        $langues = [
            ['name' => 'English', 'slug' => 'english', 'code' => 'gb'],
            ['name' => 'Français', 'slug' => 'francais', 'code' => 'fr'],
            ['name' => 'Español', 'slug' => 'espagnole', 'code' => 'es'],
            ['name' => 'Deutsch', 'slug' => 'deutsch', 'code' => 'de'],
            ['name' => 'Русский', 'slug' => 'russian', 'code' => 'rs'],
        ];        
        
        $secteurs = [
            ['name' => 'IT - Devéloppement', 'slug' => 'it-developpement'],
            ['name' => 'Marketing Digital', 'slug' => 'marketing-digital'],
            ['name' => 'Commercial', 'slug' => 'commercial'],
            ['name' => 'Recrutement', 'slug' => 'recrutement'],
            ['name' => 'RH - Administration', 'slug' => 'rh-administration'],
            ['name' => 'Finance', 'slug' => 'finance'],
            ['name' => 'Construction', 'slug' => 'construction'],
            ['name' => 'Immobilier', 'slug' => 'immobilier'],
            ['name' => 'Transport et logistique', 'slug' => 'transport-et-logistique'],
            ['name' => 'Éducation', 'slug' => 'education'],
        ];        

        foreach ($langues as $value) {
            $language = new Langue();
            $language
                ->setNom($value['name'])
                ->setSlug($value['slug'])
                ->setCode($value['code'])
            ;
            $this->em->persist($language);
        }

        foreach ($secteurs as $value) {
            $secteur = new Secteur();
            $secteur
                ->setNom($value['name'])
                ->setSlug($value['slug'])
            ;
            $this->em->persist($secteur);
        }

        $this->em->flush();
    }

    public function getModerateurEmails(): array
    {
        $emails = [];
        foreach($this->moderateurProfileRepository->findAll() as $value){
            $emails[] = $value->getModerateur()->getEmail();
        }
        
        return $emails;
    }

    public function getModerateurs(): array
    {
        $moderateurs = [];
        foreach($this->moderateurProfileRepository->findAll() as $value){
            $moderateurs[] = $value->getModerateur();
        }
        
        return $moderateurs;
    }

    public function initSector(): Secteur
    {
        return new Secteur();
    }

    public function saveSector(Secteur $secteur): void
    {
		$this->em->persist($secteur);
        $this->em->flush();
    }

    public function saveSectorForm(Form $form)
    {
        $secteur = $form->getData();
        $secteur->setSlug($this->sluggerInterface->slug(strtolower($secteur->getNom())));
        $this->saveSector($secteur);

        return $secteur;

    }

    public function initDevise(): Devise
    {
        return new Devise();
    }

    public function saveDevise(Devise $devise): void
    {
		$this->em->persist($devise);
        $this->em->flush();
    }

    public function saveDeviseForm(Form $form)
    {
        $devise = $form->getData();
        $devise->setSlug($this->sluggerInterface->slug(strtolower($devise->getNom())));
        $this->saveDevise($devise);

        return $devise;

    }

    public function findAllOrderDesc($repository)
    {
        return $repository->createQueryBuilder('entity')
            ->orderBy('entity.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deleteSector(Secteur $secteur): void
    {
		$this->em->remove($secteur);
        $this->em->flush();
    }

    public function initTypeContrat(): TypeContrat
    {
        return new TypeContrat();
    }

    public function saveTypeContrat(TypeContrat $typeContrat): void
    {
		$this->em->persist($typeContrat);
        $this->em->flush();
    }

    public function saveTypeContratForm(Form $form)
    {
        $typeContrat = $form->getData();
        $typeContrat->setSlug($this->sluggerInterface->slug(strtolower($typeContrat->getNom())));
        $this->saveTypeContrat($typeContrat);

        return $typeContrat;

    }

    public function deleteTypeContrat(TypeContrat $typeContrat): void
    {
		$this->em->remove($typeContrat);
        $this->em->flush();
    }

    public function findAllListingJob(): array
    {
        return $this->jobListingRepository->findAllOrderedByIdDesc();
    }

    public function findAllEntreprise(): array
    {
        $queryBuilder = $this->entrepriseProfileRepository->createQueryBuilder('m')
            ->orderBy('m.id', 'DESC')
            ->getQuery();

        return $queryBuilder->getResult();
    }

    public function findAllAnnonceByEntreprise(EntrepriseProfile $entreprise): array
    {
        return $this->jobListingRepository->findBy(
            ['entreprise' => $entreprise],
            ['id' => 'DESC'],
        );
    }

    public function findAllCandidat(): array
    {
        $queryBuilder = $this->candidateProfileRepository->createQueryBuilder('m')
            ->orderBy('m.id', 'DESC')
            ->getQuery();

        return $queryBuilder->getResult();
    }

    public function getSecteurChoice(): array
    {
        $choices = [];
        $secteurs =  $this->secteurRepository->findAll();
        foreach($secteurs as $secteur){
            $choices[$secteur->getNom()] = $secteur->getSlug();
        }
        
        return $choices;
    }

    public function searchSecteur(string $query = null): array
    {
        if (empty($query)) {
            return $this->secteurRepository->findAll();
        }

        $qb = $this->em->createQueryBuilder();

        $keywords = array_filter(explode(' ', $query));
        $parameters = [];
        $conditions = [];

        foreach ($keywords as $key => $keyword) {
            $conditions[] = '(s.nom LIKE :query' . $key .')';
            $parameters['query' . $key] = '%' . $keyword . '%';
        }

        $qb->select('s')
            ->from('App\Entity\Secteur', 's')
            ->where(implode(' OR ', $conditions))
            ->setParameters($parameters);

        return $qb->getQuery()->getResult();
    }

    public function searchTypeContrat(string $query = null): array
    {
        if (empty($query)) {
            return $this->typeContratRepository->findAll();
        }

        $qb = $this->em->createQueryBuilder();

        $keywords = array_filter(explode(' ', $query));
        $parameters = [];
        $conditions = [];

        foreach ($keywords as $key => $keyword) {
            $conditions[] = '(t.nom LIKE :query' . $key .')';
            $parameters['query' . $key] = '%' . $keyword . '%';
        }

        $qb->select('t')
            ->from('App\Entity\Moderateur\TypeContrat', 't')
            ->where(implode(' OR ', $conditions))
            ->setParameters($parameters);

        return $qb->getQuery()->getResult();
    }

    public function searchDevise(string $query = null): array
    {
        if (empty($query)) {
            return $this->em->getRepository(Devise::class)->findAll();
        }

        $qb = $this->em->createQueryBuilder();

        $keywords = array_filter(explode(' ', $query));
        $parameters = [];
        $conditions = [];

        foreach ($keywords as $key => $keyword) {
            $conditions[] = '(d.nom LIKE :query' . $key .')';
            $parameters['query' . $key] = '%' . $keyword . '%';
        }

        $qb->select('d')
            ->from('App\Entity\Finance\Devise', 'd')
            ->where(implode(' OR ', $conditions))
            ->setParameters($parameters);

        return $qb->getQuery()->getResult();
    }

    public function searchAnnonce(?string $titre = null, ?string $entreprise = null, ?string $status = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $parameters = [];
        $conditions = [];

        if($titre == null && $entreprise == null && $status == null){
            return $this->findAllListingJob();
        }

        if (!empty($titre)) {
            $conditions[] = '(j.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($entreprise)) {
            $conditions[] = '(e.nom LIKE :entreprise )';
            $parameters['entreprise'] = '%' . $entreprise . '%';
        }

        if (!empty($status)) {
            $conditions[] = '(j.status LIKE :status )';
            $parameters['status'] = '%' . $status . '%';
        }

        $qb->select('j')
            ->from('App\Entity\Entreprise\JobListing', 'j')
            ->leftJoin('j.entreprise', 'e')
            ->where(implode(' AND ', $conditions))
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }

    public function searchCandidat(?string $nom = null, ?string $titre = null, ?string $status = null, ?string $availability = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $parameters = [];
        $conditions = [];

        if($nom == null && $titre == null && $status == null && $availability == null){
            return $this->findAllCandidat();
        }

        if (!empty($titre)) {
            $conditions[] = '(c.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($nom)) {
            $conditions[] = '(u.nom LIKE :nom OR u.prenom LIKE :nom OR u.email LIKE :nom )';
            $parameters['nom'] = '%' . $nom . '%';
        }

        if (!empty($status)) {
            $conditions[] = '(c.status LIKE :status )';
            $parameters['status'] = '%' . $status . '%';
        }

        if (!empty($availability)) {
            $conditions[] = '(a.nom LIKE :availability )';
            $parameters['availability'] = '%' . $availability . '%';
        }

        $qb->select('c')
            ->from('App\Entity\CandidateProfile', 'c')
            ->leftJoin('c.candidat', 'u')
            ->leftJoin('c.availability', 'a')
            ->where(implode(' AND ', $conditions))
            ->setParameters($parameters)
            ->orderBy('c.id', 'DESC'); 
        
        return $qb->getQuery()->getResult();
    }

    public function findAllAnnonceEntreprise(EntrepriseProfile $entreprise, ?string $nom = null, ?string $secteur = null, ?string $status = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $parameters = [
            'entreprise' => $entreprise,
        ];

        $conditions = [];

        if($nom == null && $secteur == null && $status == null){
            return $this->findAllAnnonceByEntreprise($entreprise);
        }

        if (!empty($nom)) {
            $conditions[] = '(j.titre LIKE :nom )';
            $parameters['nom'] = '%' . $nom . '%';
        }

        if (!empty($secteur)) {
            $conditions[] = '(s.nom LIKE :secteur )';
            $parameters['secteur'] = '%' . $secteur . '%';
        }

        if (!empty($status)) {
            $conditions[] = '(j.status LIKE :status )';
            $parameters['status'] = '%' . $status . '%';
        }

        $qb->select('j')
            ->from('App\Entity\Entreprise\JobListing', 'j')
            ->leftJoin('j.secteur', 's')
            ->where(implode(' AND ', $conditions))
            ->andWhere('j.entreprise = :entreprise')
            ->orderBy('j.id', 'DESC')
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }

    public function searchEntreprise(?string $nom = null, ?string $secteur = null, ?string $status = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $parameters = [];
        $conditions = [];

        if($nom == null && $secteur == null && $status == null){
            return $this->findAllEntreprise();
        }

        if (!empty($secteur)) {
            $conditions[] = '(s.nom LIKE :secteur )';
            $parameters['secteur'] = '%' . $secteur . '%';
        }

        if (!empty($nom)) {
            $conditions[] = '(e.nom LIKE :nom )';
            $parameters['nom'] = '%' . $nom . '%';
        }

        if (!empty($status)) {
            $conditions[] = '(e.status LIKE :status )';
            $parameters['status'] = '%' . $status . '%';
        }

        $qb->select('e')
            ->from('App\Entity\EntrepriseProfile', 'e')
            ->leftJoin('e.secteurs', 's')
            ->where(implode(' AND ', $conditions))
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }

    public function findAllCandidatures(?string $titre = null, ?string $entreprise = null, ?string $candidat = null, ?string $status = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $parameters = [];
        $conditions = [];

        if($titre == null && $entreprise == null && $candidat == null && $status == null){
            $queryBuilder = $this->applicationsRepository->createQueryBuilder('a')
                ->orderBy('a.id', 'DESC')
                ->getQuery();

            return $queryBuilder->getResult();;
        }

        if (!empty($entreprise)) {
            $conditions[] = '(e.nom LIKE :entreprise )';
            $parameters['entreprise'] = '%' . $entreprise . '%';
        }

        if (!empty($titre)) {
            $conditions[] = '(p.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($status)) {
            $conditions[] = '(a.status LIKE :status )';
            $parameters['status'] = '%' . $status . '%';
        }

        if (!empty($candidat)) {
            $conditions[] = '(u.nom LIKE :candidat )';
            $parameters['candidat'] = '%' . $candidat . '%';
        }

        $qb->select('a')
            ->from('App\Entity\Candidate\Applications', 'a')
            ->leftJoin('a.annonce', 'p')
            ->leftJoin('a.candidat', 'c')
            ->leftJoin('c.candidat', 'u')
            ->leftJoin('p.entreprise', 'e')
            ->where(implode(' AND ', $conditions))
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }

}
