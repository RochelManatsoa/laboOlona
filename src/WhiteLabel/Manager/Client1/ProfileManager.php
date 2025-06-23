<?php

namespace App\WhiteLabel\Manager\Client1;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Form;
use Symfony\Component\Uid\Uuid;
use App\WhiteLabel\Entity\Client1\User;
use App\WhiteLabel\Entity\Client1\CandidateProfile;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use App\WhiteLabel\Entity\Client1\Candidate\CV;

class ProfileManager
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntityManagerInterface $entityManager,
    ) {
        $this->entityManager = $managerRegistry->getManager('client1');
    }

    public function createCandidat(User $user): CandidateProfile
    {
        $candidate = new CandidateProfile();
        $candidate->setCandidat($user);
        $candidate->setIsValid(false);
        $candidate->setStatus(CandidateProfile::STATUS_PENDING);
        $candidate->setUid(new Uuid(Uuid::v1()));

        return $candidate;
    }

    public function createCompany(User $user): EntrepriseProfile
    {
        $company = new EntrepriseProfile();
        $company->setEntreprise($user);
        $company->setStatus(EntrepriseProfile::STATUS_PENDING);

        return $company;
    }

    public function saveCandidate(CandidateProfile $candidate): void
    {
        $this->entityManager->persist($candidate);
        $this->entityManager->flush();
    }

    public function saveCompany(EntrepriseProfile $company): void
    {
        $this->entityManager->persist($company);
        $this->entityManager->flush();
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

    public function saveCV(array $fileName, CandidateProfile $candidate): void
    {
        $cv = new CV();
        $cv
            ->setCvLink($fileName[0])
            ->setSafeFileName($fileName[1])
            ->setUploadedAt(new DateTime())
            ->setCandidat($candidate);

        if (!$this->entityManager->contains($candidate)) {
            $this->entityManager->persist($candidate);
        }

        $this->entityManager->persist($cv);
        $this->entityManager->flush();
    }
}
