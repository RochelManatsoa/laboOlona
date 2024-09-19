<?php

namespace App\Form\Finance;

use App\Entity\Finance\Employe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SimulateurEmployeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateEmbauche')
            ->add('nombreEnfants')
            ->add('matricule')
            ->add('cnaps')
            ->add('sexe')
            ->add('cin')
            ->add('dateNaissance')
            ->add('categorie')
            ->add('fonction')
            ->add('salaireBase')
            ->add('congePris')
            ->add('user')
            ->add('salaire')
            ->add('avantage')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employe::class,
        ]);
    }
}
