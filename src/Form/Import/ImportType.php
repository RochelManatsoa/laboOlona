<?php

namespace App\Form\Import;

use App\Data\ImportData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('per_page', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'choices' => [
                    '10 par page' => 10,
                    '20 par page' => 20,
                    '50 par page' => 50,
                ],
                'attr' => [
                    'placeholder' => 'Par page'
                ]
            ])
            ->add('page', NumberType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Page'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImportData::class,
            'method' => 'GET',
            'csrf_=>protection' => false
        ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}