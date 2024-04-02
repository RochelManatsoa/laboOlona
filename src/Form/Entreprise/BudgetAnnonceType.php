<?php

namespace App\Form\Entreprise;

use App\Entity\Entreprise\BudgetAnnonce;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BudgetAnnonceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeBudget', ChoiceType::class, [
                'choices' => BudgetAnnonce::arrayTarifType(),
                'label' => false,
            ])
            ->add('devise', ChoiceType::class, [
                'choices' => BudgetAnnonce::arrayDevise(),
                'label' => false,
            ])
            ->add('montant', TextType::class, [])   
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BudgetAnnonce::class,
        ]);
    }
}
