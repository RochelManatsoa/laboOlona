<?php

namespace App\Form\Finance;

use App\Entity\Finance\Avantage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvantageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('absence')
            ->add('hs30', NumberType::class, ['label' => 'HS 30% heures nuit habituelles', 'required' => false])
            ->add('hs40', NumberType::class, ['label' => 'HS 40% heures travaillées dimanche', 'required' => false])
            ->add('hs50', NumberType::class, ['label' => 'HS 50% heures nuit occasionnelles', 'required' => false])
            ->add('hs130', NumberType::class, ['label' => 'HS 30% heures jour (8 première) ', 'required' => false])
            ->add('hs150', NumberType::class, ['label' => 'HS 50% heures jour (restantes)', 'required' => false])
            ->add('hn', NumberType::class, ['label' => 'HS 100% jours fériers', 'required' => false])
            ->add('congePaye')
            ->add('congePris')
            ->add('primeFonction')
            ->add('primeConnexion')
            ->add('rappel')
            ->add('repas')
            ->add('deplacement')
            ->add('allocationConge')
            ->add('preavis')
            ->add('primeAvance15')
            ->add('avanceSpeciale')
            ->add('choixDeduction')
            ->add('salaireBrut')
            ->add('moySur12')
            ->add('freelance')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Avantage::class,
        ]);
    }
}
