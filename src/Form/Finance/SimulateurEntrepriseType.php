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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class SimulateurEntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('devise', EntityType::class, [
                'label' => 'Devise',
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
                    'data-id' => 'simulateur_taux',
                    'value' => $options['default_devise'] ? $options['default_devise']->getTaux() : null, 
                ]
            ])
            ->add('deviseSymbole', HiddenType::class, [
                'data' => "€", 
            ])
            ->add('salaireNet', TextType::class, [
                'label' => 'Salaire Net proposé'
            ])
            ->add('nombreEnfant', IntegerType::class, [
                'label' => 'Nombre d\'enfant',
                'required' => false,
                'data' => 0, 
            ])
            ->add('jourRepas', IntegerType::class, [
                'label' => 'Nombre de jour',
                'required' => false,
                'data' => 0, // Valeur par défaut
            ])
            ->add('jourDeplacement', IntegerType::class, [
                'label' => 'Nombre de jour',
                'required' => false,
                'data' => 0, // Valeur par défaut
            ])
            ->add('prixRepas', TextType::class, [
                'label' => 'Prix'
            ])
            ->add('prixDeplacement', TextType::class, [
                'label' => 'Prix'
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Télétravail' => 'TELETRAVAIL' ,
                    'Bureau de l\'entreprise' => 'ENTREPRISE',
                    'Espace co-working Chez Olona' => 'OLONA' ,
                ],
                'label' => 'Lieu de travail'
            ])
            ->add('avantage', SimulateurAvantageType::class, [
                'label' => false
            ])
        ;
        if ($options['connected']) {
            $builder->add('employe', EmployeFormType::class, [
                'label' => false
            ]);
        }
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
            'connected' => null, 
            'default_devise' => null, 
        ]);
    }
}
