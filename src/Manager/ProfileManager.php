<?php

namespace App\Manager;

use DateTime;
use App\Entity\User;
use App\Entity\Candidate\CV;
use Twig\Environment as Twig;
use App\Entity\ReferrerProfile;
use Symfony\Component\Uid\Uuid;
use App\Entity\CandidateProfile;
use Symfony\Component\Form\Form;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\BoostFacebook;
use App\Entity\Moderateur\EditedCv;
use App\Entity\BusinessModel\Credit;
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

    public function createReferrer($user)
    {
        $referrer = new ReferrerProfile();
        $referrer->setReferrer($user);
        $referrer->setCreatedAt(new DateTime());
        $referrer->setStatus(ReferrerProfile::STATUS_PENDING);
        $referrer->setCustomId(new Uuid(Uuid::v1()));

        return $referrer;
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

    public function saveCVEdited(array $fileName, CandidateProfile $candidat, CV $cv)
    {
        $editedCv = $cv->getEdited();
        if (!$editedCv instanceof EditedCv) {
            $editedCv = new EditedCv();
        }
        $editedCv
        ->setCvLink($fileName[0])
        ->setSafeFileName($fileName[1])
        ->setUploadedAt(new DateTime())
        ->setCandidat($candidat)
        ->setCV($cv)
        ;

        $this->em->persist($editedCv);
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

    public function canApplyBoost(User $user, Boost $boost): bool
    {
        $credit = $user->getCredit();
        $amount = $boost->getCredit();
        if ($credit instanceof Credit) {
            if($credit->getTotal() > $amount){
                return true;
            }
        }

        return false;
    }

    public function canApplyBoostFacebook(User $user, BoostFacebook $boost): bool
    {
        $credit = $user->getCredit();
        $amount = $boost->getCredit();
        if ($credit instanceof Credit) {
            if($credit->getTotal() > $amount){
                return true;
            }
        }

        return false;
    }

    public function canApplyAction(User $user, string $action): bool
    {
        $credit = $user->getCredit();
        $amount = $this->getCreditAmount($action);

        if ($credit instanceof Credit) {
            if($credit->getTotal() > $amount){
                return true;
            }
        }

        return false;
    }

    public function canBuy(User $user, int $amount): bool
    {
        $credit = $user->getCredit();

        if ($credit instanceof Credit) {
            if($credit->getTotal() > $amount){
                return true;
            }
        }

        return false;
    }

    public function getCreditAmount(string $action): float
    {
        switch ($action) {
            case Credit::ACTION_VIEW_CANDIDATE :
                $amount = 50;
                break;

            case Credit::ACTION_VIEW_RECRUITER :
                $amount = 50;
                break;
                
            case Credit::ACTION_UPLOAD_CV :
                $amount = 15;
                break;
                
            case Credit::ACTION_APPLY_OFFER :
                $amount = 15;
                break;
                
            case Credit::ACTION_APPLY_JOB :
                $amount = 10;
                break;
                
            case Credit::ACTION_APPLY_PRESTATION_CANDIDATE :
                $amount = 10;
                break;
                
            case Credit::ACTION_APPLY_PRESTATION_RECRUITER :
                $amount = 10;
                break;
            
            default:
                $amount = 0;
                break;
        }

        return $amount;
    }
}
