<?php

namespace App\Manager;

use App\Entity\Finance\Simulateur;
use App\Entity\Prestation;
use Symfony\Component\Form\Form;
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