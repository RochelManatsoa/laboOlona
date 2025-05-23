<?php

namespace App\Manager;

use DateTime;
use App\Entity\User;
use Twig\Environment as Twig;
use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use Symfony\Component\Form\Form;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Entity\Moderateur\Metting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\Moderateur\MettingRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class RendezVousManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private MettingRepository $mettingRepository,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security
    ){}

    public function createRendezVous(
        ModerateurProfile $moderateur, 
        CandidateProfile $candidat, 
        EntrepriseProfile $entreprise,
        JobListing $annonce
    ) : Metting
    {
        $rendezVous = new Metting();
        $uuid = new Uuid(Uuid::v4());
        $rendezVous->setCustomId($uuid);
        $rendezVous->setLink($this->urlGenerator->generate(
            'app_dashboard_conference',
            [
                'uuid' => $uuid
            ], 
            UrlGeneratorInterface::ABSOLUTE_URL
        ));
        $rendezVous->setModerateur($moderateur);
        $rendezVous->setEntreprise($entreprise);
        $rendezVous->setCandidat($candidat);
        $rendezVous->setCreeLe(new DateTime());
        $rendezVous->setTitle($annonce->getTitre());
        $rendezVous->setStatus(Metting::STATUS_PENDING);

        return $rendezVous;
    }

    public function save(Metting $rendezVous)
    {
		$this->em->persist($rendezVous);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $this->save($form->getData());
    }

    public function getUserRole(User $user)
    {
        switch($user->getType()){
            case User::ACCOUNT_CANDIDAT :
                $role = $user->getCandidateProfile();
            break;

            case User::ACCOUNT_ENTREPRISE :
                $role = $user->getEntrepriseProfile();
            break;

            case User::ACCOUNT_MODERATEUR :
                $role = $user->getModerateurProfile();
            break;
        }

        return $role;
    }
    
    public function getUserTypeByRole($profile)
    {
        if ($profile instanceof CandidateProfile) {
            return User::ACCOUNT_CANDIDAT;
        }

        if ($profile instanceof EntrepriseProfile) {
            return User::ACCOUNT_ENTREPRISE;
        }

        if ($profile instanceof ModerateurProfile) {
            return User::ACCOUNT_MODERATEUR;
        }

        return null;
    }
    public function getUserByRole($profile)
    {
        if ($profile instanceof CandidateProfile) {
            return $profile->getCandidat();
        }

        if ($profile instanceof EntrepriseProfile) {
            return $profile->getEntreprise();
        }

        if ($profile instanceof ModerateurProfile) {
            return $profile->getModerateur();
        }

        return null;
    }

    public function findMettingByRole($role) : array
    {
        if($role instanceof EntrepriseProfile){
            return $this->mettingRepository->findBy(
                ['entreprise' => $role],
                ['id' => 'DESC']
            );
        }

        if($role instanceof CandidateProfile){
            return $this->mettingRepository->findBy(
                ['candidat' => $role],
                ['id' => 'DESC']
            );
        }

        if($role instanceof ModerateurProfile){
            return $this->mettingRepository->findBy(
                ['moderateur' => $role],
                ['id' => 'DESC']
            );
        }
    }
}