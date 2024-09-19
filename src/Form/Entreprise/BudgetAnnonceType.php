<?php

namespace App\Form\Entreprise;

use App\Entity\Finance\Devise;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Entity\Entreprise\BudgetAnnonce;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class BudgetAnnonceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeBudget', ChoiceType::class, [
                'choices' => BudgetAnnonce::arrayTarifType(),
                'label' => false,
            ])
            ->add('montant', IntegerType::class,[
                'label' => false,
                'attr' => [
                    'class' => '',                    
                ],
            ])
            ->add('currency', EntityType::class, [
                'label' => false,
                'mapped' => true,
                'class' => Devise::class, 
                'attr' => [
                    'data-controller' => 'budget-taux',
                    'data-action' => 'change->budget-taux#onDeviseChange'
                ],
            ])  
            ->add('taux', HiddenType::class, [
                'attr' =>  [
                    'data-id' => 'budgetAnnonce_taux',
                    'value' => $options['default_devise'] ? $options['default_devise']->getTaux() : null, 
                ],
                'data' => $options['default_devise'] ? $options['default_devise']->getTaux() : '4000', 
            ])
            ->add('devise', HiddenType::class, [
                'attr' =>  [
                    'data-id' => 'budgetAnnonce_symbole',
                ],
                'data' => "€", 
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BudgetAnnonce::class,
            'default_devise' => null, 
        ]);
    }
}
