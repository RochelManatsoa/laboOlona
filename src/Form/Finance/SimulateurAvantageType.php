<?php

namespace App\Form\Finance;

use App\Entity\Finance\Avantage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SimulateurAvantageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('primeConnexion', TextType::class, [
                'label' => 'Connexion internet',
                'required' => false,
            ])
            ->add('repas', TextType::class, [
                'label' => 'Repas journalier',
                'required' => false,
            ])
            ->add('deplacement', TextType::class, [
                'label' => 'Déplacement',
                'required' => false,
            ])
            ->add('primeFonction', TextType::class, [
                'label' => 'Autres frais',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Avantage::class,
        ]);
    }
}
