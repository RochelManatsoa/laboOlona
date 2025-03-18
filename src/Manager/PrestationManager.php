<?php

namespace App\Manager;

use App\Entity\User;
use App\Entity\Prestation;
use App\Entity\CandidateProfile;
use Symfony\Component\Form\Form;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;

class PrestationManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
    ){}

    public function init(?User $user): Prestation
    {
        $prestation = new Prestation();
        $prestation->setCreatedAt(new \DateTime());
        $prestation->setStatus(Prestation::STATUS_PENDING);
        $prestation->setIsGenerated(false);
        if($user instanceof User){
            $prestation->setContactEmail($user->getEmail());
            $prestation->setContactTelephone($user->getTelephone());
            $profile = $this->userService->checkUserProfile($user);
            if($profile instanceof CandidateProfile){
                $prestation->setCandidateProfile($profile);
            }
            if($profile instanceof EntrepriseProfile){
                $prestation->setEntrepriseProfile($profile);
            }
        }

        return $prestation;
    }

    public function save(Prestation $prestation)
    {
        $this->em->persist($prestation);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $prestation = $form->getData();
        $this->save($prestation);

        return $prestation;
    }
}