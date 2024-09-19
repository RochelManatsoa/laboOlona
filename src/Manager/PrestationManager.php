<?php

namespace App\Manager;

use App\Entity\Prestation;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;

class PrestationManager
{
    public function __construct(
        private EntityManagerInterface $em,
    ){}

    public function init(): Prestation
    {
        $prestation = new Prestation();
        $prestation->setCreatedAt(new \DateTime());
        $prestation->setStatus(Prestation::STATUS_PENDING);
        $prestation->setIsGenerated(false);

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