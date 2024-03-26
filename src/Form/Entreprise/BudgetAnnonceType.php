<?php

namespace App\Form\Entreprise;

use App\Entity\Entreprise\BudgetAnnonce;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class BudgetAnnonceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', MoneyType::class, [])   
            ->add('devise', ChoiceType::class, [
                'choices' => BudgetAnnonce::arrayDevise(),
                'label' => false,
            ])
            ->add('typeBudget', ChoiceType::class, [
                'choices' => BudgetAnnonce::arrayTarifType(),
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BudgetAnnonce::class,
        ]);
    }
}
