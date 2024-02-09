<?php

namespace App\Form\Candidat;

use App\Entity\Candidate\TarifCandidat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class TarifCandidatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', IntegerType::class, [
                'required' => false,
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.']),
                'label' => false,
            ])
            ->add('devise', ChoiceType::class, [
                'choices' => TarifCandidat::arrayDevise(),
                'label' => false,
            ])
            ->add('typeTarif', ChoiceType::class, [
                'choices' => TarifCandidat::arrayTarifType(),
                'label' => false,
            ])
            // ->add('candidat')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TarifCandidat::class,
        ]);
    }
}
