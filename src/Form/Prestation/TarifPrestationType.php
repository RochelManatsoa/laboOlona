<?php

namespace App\Form\Prestation;

use App\Entity\Prestation;
use App\Entity\Finance\Devise;
use Symfony\Component\Form\AbstractType;
use App\Entity\Prestation\TarifPrestation;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class TarifPrestationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', IntegerType::class, [
                'required' => false,
                'constraints' =>  new Sequentially([
                    new NotBlank(message:'Le montant est obligatoire.'),
                    new PositiveOrZero(message:'Format invalide.'),
                ]),
                'label' => false,
            ])
            ->add('currency', EntityType::class, [
                'label' => false,
                'mapped' => true,
                'class' => Devise::class, 
                'attr' => [
                    'data-controller' => 'tarif-devise',
                    'data-action' => 'change->tarif-devise#onDeviseChange'
                ],
            ])
            ->add('typeTarif', ChoiceType::class, [
                'choices' => TarifPrestation::arrayTarifType(),
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TarifPrestation::class,
        ]);
    }
}
