<?php

namespace App\Form\Prestation;

use App\Entity\Availability;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AvailabilityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $yearNow = date('Y');
        $yearEnd = $yearNow + 10; 
        $builder
            ->add('nom', ChoiceType::class, [
                'choices' => Availability::CHOICE_TYPE,
                'label_attr' => [ 'class' => 'd-flex align-items-center'],
                'multiple' => false,
                'label' => 'Disponibilité',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Choisissez les jours et heures où vous êtes disponible pour cette prestation.',
            ])
            ->add('dateFin', DateType::class, [
                'placeholder' => [
                    'year' => 'Année', 'month' => 'Mois', 'day' => 'Jour'
                ],
                'required' => false,
                'attr' => ['class' => 'date-picker'],
                'label' => false,
                'years' => range($yearNow, $yearEnd),
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Availability::class,
        ]);
    }
}
