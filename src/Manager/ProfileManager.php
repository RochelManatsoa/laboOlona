<?php

namespace App\Manager;

use App\Entity\Candidate\CV;
use Twig\Environment as Twig;
use Symfony\Component\Uid\Uuid;
use App\Entity\CandidateProfile;
use Symfony\Component\Form\Form;
use App\Entity\EntrepriseProfile;
use App\Entity\Moderateur\EditedCv;
use App\Entity\ModerateurProfile;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private Security $security
    ) {
    }

    public function createCompany($user)
    {
        $company = new EntrepriseProfile();
        $company->setEntreprise($user);
        $company->setStatus(EntrepriseProfile::STATUS_PENDING);

        return $company;
    }

    public function createCandidat($user)
    {
        $candidate = new CandidateProfile();
        $candidate->setCandidat($user);
        $candidate->setIsValid(false);
        $candidate->setStatus(CandidateProfile::STATUS_PENDING);
        $candidate->setUid(new Uuid(Uuid::v1()));

        return $candidate;
    }

    public function createModerateur($user)
    {
        $moderateur = new ModerateurProfile();
        $moderateur->setModerateur($user);
        $this->saveModerateur($moderateur);

        return $moderateur;
    }

    public function saveCandidate(CandidateProfile $candidate)
    {
        $this->em->persist($candidate);
        $this->em->flush();
    }

    public function saveCompany(EntrepriseProfile $company)
    {
        $this->em->persist($company);
        $this->em->flush();
    }

    public function saveModerateur(ModerateurProfile $moderateur)
    {
        $this->em->persist($moderateur);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $profile = $form->getData();
        if ($profile instanceof EntrepriseProfile) {
            $this->saveCompany($profile);
        }
        if ($profile instanceof CandidateProfile) {
            $this->saveCandidate($profile);
        }

        return $profile;
    }

    public function saveCV(array $fileName, CandidateProfile $candidat)
    {
        $cv = new CV();
        $cv
        ->setCvLink($fileName[0])
        ->setSafeFileName($fileName[1])
        ->setUploadedAt(new DateTime())
        ->setCandidat($candidat)
        ;
        
        // Vérifiez si l'entité CandidateProfile est déjà gérée
        if (!$this->em->contains($candidat)) {
            // Si ce n'est pas le cas, persistez l'entité CandidateProfile
            $this->em->persist($candidat);
        }

        $this->em->persist($cv);
        $this->em->flush();
    }

    public function saveCVEdited(array $fileName, CandidateProfile $candidat)
    {
        $cv = new EditedCv();
        $cv
        ->setCvLink($fileName[0])
        ->setSafeFileName($fileName[1])
        ->setUploadedAt(new DateTime())
        ->setCandidat($candidat)
        ;

        $this->em->persist($cv);
        $this->em->flush();
    }

    private function getFormattedFileName($originalFileName)
    {
        // On divise le nom du fichier par les tirets
        $parts = explode('-', $originalFileName);
    
        // On retire l'avant-dernier élément (l'identifiant unique)
        array_splice($parts, -2, 1);
    
        // On rejoint les parties restantes
        $formattedFileName = implode('-', $parts);
    
        return $formattedFileName;
    }
}
