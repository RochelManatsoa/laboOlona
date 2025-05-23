<?php

namespace App\Manager;

use App\Entity\User;
use App\Entity\Prestation;
use App\Entity\Finance\Employe;
use Symfony\Component\Form\Form;
use App\Entity\Finance\Simulateur;
use Doctrine\ORM\EntityManagerInterface;

class SimulatorManager
{
    public function __construct(
        private EntityManagerInterface $em,
    ){}

    public function init(): Simulateur
    {
        $simulateur = new Simulateur();
        $simulateur->setCreatedAt(new \DateTime());
        $simulateur->setStatus(Simulateur::STATUS_LIBRE);

        return $simulateur;
    }

    public function initSimulateur(User $user): Simulateur
    {
        $simulateur = $this->init();
        $employe = $user->getEmploye();
        if(!$employe instanceof Employe){
            $employe = new Employe();
            $employe->setUser($user);
        }
        $simulateur->setEmploye($employe);

        return $simulateur;
    }

    public function save(Simulateur $simulateur)
    {
        $this->em->persist($simulateur);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $simulateur = $form->getData();
        $this->save($simulateur);

        return $simulateur;
    }
}