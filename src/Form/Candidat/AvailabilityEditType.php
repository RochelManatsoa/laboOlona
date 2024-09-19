<?php

namespace App\Form\Candidat;

use App\Entity\Availability;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AvailabilityEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $yearNow = date('Y');
        $yearEnd = $yearNow + 10; 
        $builder
            ->add('dateFin', DateType::class, [
                'placeholder' => [
                    'year' => 'Année', 'month' => 'Mois', 'day' => 'Jour'
                ],
                'required' => false,
                'attr' => ['class' => 'date-picker'],
                'label' => false,
                'years' => range($yearNow, $yearEnd),
            ])
            ->add('nom', ChoiceType::class, [
                'choices' => [
                    'Immédiatement' => 'immediate',
                    'A partir du' => 'from-date',
                    'Temps plein' => 'full-time',
                    'Temps partiel' => 'part-time',
                    'En poste (non disponible)' => 'not-available',
                ],
                'label_attr' => [ 'class' => 'd-flex align-items-center'],
                'multiple' => false,
                'label' => 'Disponibilité',
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Availability::class,
        ]);
    }
}
