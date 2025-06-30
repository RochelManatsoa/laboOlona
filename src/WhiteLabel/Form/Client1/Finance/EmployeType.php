<?php

namespace App\WhiteLabel\Form\Client1\Finance;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\WhiteLabel\Entity\Client1\Finance\Employe;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\WhiteLabel\Form\Client1\Autocomplete\UserAutocompleteField;
use App\WhiteLabel\Form\Client1\Profile\Candidat\Edit\InfoUserType;

class EmployeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', UserAutocompleteField::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Selectionner l\'utilisateur',
            ])
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
            // ->add('avantage', AvantageType::class, [
            //     'label' => false,
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employe::class,
        ]);
    }
}
