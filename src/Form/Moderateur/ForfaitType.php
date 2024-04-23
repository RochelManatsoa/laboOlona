<?php

namespace App\Form\Moderateur;

use App\Entity\Finance\Devise;
use App\Entity\Moderateur\Forfait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class ForfaitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', IntegerType::class, [
                'required' => false,
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.']),
                'label' => false,
            ])
            ->add('currency', EntityType::class, [
                'label' => false,
                'mapped' => true,
                'class' => Devise::class, 
                'attr' => [
                    'data-controller' => 'forfait-devise',
                    'data-action' => 'change->forfait-devise#onDeviseChange'
                ],
            ])   
            ->add('devise', HiddenType::class, [
                'attr' =>  [
                    'data-id' => 'forfait_symbole',
                ],
                'data' => "€", 
            ])
            ->add('typeForfait', ChoiceType::class, [
                'choices' => Forfait::arrayTarifType(),
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Forfait::class,
        ]);
    }
}
