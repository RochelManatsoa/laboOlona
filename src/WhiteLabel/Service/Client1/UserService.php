<?php

namespace App\WhiteLabel\Service\Client1;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use App\WhiteLabel\Repository\Client1\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\WhiteLabel\Entity\Client1\{CandidateProfile, EntrepriseProfile, ReferrerProfile, User};

class UserService
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private Security $security,
        private UserRepository $userRepository,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $encoder,
    ){
        $this->entityManager = $managerRegistry->getManager('client1');
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
            
            default:
                $profile = null;
                break;
        }

        return $profile;
    }

    public function checkUserProfile(User $user)
    {
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
        $user->setDateInscription(new \DateTime());
        
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

    public function initReferrer(User $user)
    {
        $profilReferrer = new ReferrerProfile();
        $profilReferrer->setReferrer($user);
        $profilReferrer->setStatus(ReferrerProfile::STATUS_PENDING);
        $profilReferrer->setCustomId(new Uuid(Uuid::v1()));
        
        return $profilReferrer;
    }

    public function save(User $user)
    {
		$this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function saveEntreprise(EntrepriseProfile $profilEntreprise)
    {
		$this->entityManager->persist($profilEntreprise);
        $this->entityManager->flush();
    }

    public function saveCandidat(CandidateProfile $profilCandidat)
    {
		$this->entityManager->persist($profilCandidat);
        $this->entityManager->flush();
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
                $user->setDateInscription(new \DateTime())
                ->setEmail($email)
                ->setPassword($this->encoder->hashPassword($user, $plainPassword))
            ;
        }

        $user
            ->setNom('Olona')
            ->setType(User::ACCOUNT_MODERATEUR)
            ->setPrenom('Modérateur')
        ;

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;

    }

    public function checkUserPassword(User $user, string $plainPassword):bool
    {
        return $this->encoder->isPasswordValid($user, $plainPassword);
    }
}