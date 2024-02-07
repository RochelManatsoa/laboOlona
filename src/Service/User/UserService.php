<?php

namespace App\Service\User;

use App\Entity\{CandidateProfile, EntrepriseProfile, ModerateurProfile, User};
use App\Repository\{CandidateProfileRepository, EntrepriseProfileRepository, UserRepository};
use DateTime;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private RequestStack $requestStack,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $encoder,
    ){
    }

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
}