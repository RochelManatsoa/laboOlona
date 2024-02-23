<?php

namespace App\Form\Finance;

use App\Entity\User;
use App\Entity\Finance\Employe;
use Symfony\Component\Form\AbstractType;
use App\Form\Profile\Candidat\Edit\InfoUserType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class EmployeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', InfoUserType::class, ['label' => false])
            ->add('dateEmbauche', DateType::class, [
                'widget' => 'single_text',  
            ])
            ->add('nombreEnfants')
            ->add('matricule')
            ->add('cnaps')
            ->add('sexe', ChoiceType::class, [
                'choices' => [
                    'Masculin' => 0,
                    'Féminin' => 1,
                ],
            ])
            ->add('cin')
            ->add('dateNaissance', DateType::class, [
                'widget' => 'single_text',  
            ])
            ->add('categorie')
            ->add('fonction')
            ->add('salaireBase')
            ->add('congePris')
            ->add('avantage', AvantageType::class, [
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employe::class,
        ]);
    }
}
