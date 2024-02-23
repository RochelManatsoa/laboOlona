<?php

namespace App\Form\Finance;

use App\Entity\Finance\Devise;
use App\Entity\Finance\Simulateur;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class SimulateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('devise', EntityType::class, [
                'label' => 'Indiquez votre devise',
                'mapped' => true,
                'class' => Devise::class,
                'attr' => [
                    'data-label' => 'SimulateurType::getSymboleDevise',
                    'data-controller' => 'devise-taux',
                    'data-action' => 'change->devise-taux#onDeviseChange'
                ],
            ])
            ->add('taux', TextType::class, [
                'label' => 'Taux de change souhaité ',
                'attr' =>  [
                    'placeholder' => 'Taux de change ',
                    ]
                ])
            ->add('salaireNet', TextType::class, [
                'label' => 'Indiquez votre salaire Net souhaité'
            ])
            ->add('nombreEnfant', IntegerType::class, [
                'label' => 'Nombre d\'enfant à charge',
                'required' => false,
                'data' => 0, // Valeur par défaut
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Je travaille de chez moi' => 'FREELANCE' ,
                    'Je souhaite avoir un bureau chez OLONA' => 'EMPLOYER',
                ],
                'label' => 'Quelle est votre situation'
            ])
            ->add('avantage', SimulateurAvantageType::class, [
                'label' => false
            ])
            ->add('employe', EmployeFormType::class, [
                'label' => false
            ])
        ;
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $devise = $form->get('devise')->getData();
            
            if ($devise) {
                $taux = $form->get('taux')->getData();
                $form->get('taux')->setData($taux);
            }
        });
    }

    public function getSymboleDevise(Devise $devise): string
    {
        return $devise->getSymbole();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Simulateur::class,
        ]);
    }
}
