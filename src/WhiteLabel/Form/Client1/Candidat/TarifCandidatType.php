<?php

namespace App\WhiteLabel\Form\Client1\Candidat;

use App\WhiteLabel\Entity\Client1\Finance\Devise;
use App\WhiteLabel\Entity\Client1\Candidate\TarifCandidat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class TarifCandidatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', IntegerType::class, [
                'required' => false,
                'label' => 'Montant',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Champ obligatoire.'),
                ]),
                'row_attr' => ['class' => 'col-md-6'],
            ])
            ->add('typeTarif', ChoiceType::class, [
                'choices' => TarifCandidat::arrayTarifType(),
                'label' => 'Type',
                'row_attr' => ['class' => 'col-md-6'],
            ])
            ->add('currency', EntityType::class, [
                'label' => 'Devise',
                'mapped' => true,
                'class' => Devise::class, 
                'attr' => [
                    'data-controller' => 'tarif-devise',
                    'data-action' => 'change->tarif-devise#onDeviseChange'
                ],
                'row_attr' => ['class' => 'col-md-6'],
            ])   
            ->add('devise', HiddenType::class, [
                'attr' =>  [
                    'data-id' => 'tarif_symbole',
                ],
                'data' => '€',  
                'required' => true,
                'empty_data' => '€', 
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TarifCandidat::class,
        ]);
    }
}
