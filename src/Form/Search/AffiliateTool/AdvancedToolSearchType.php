<?php

namespace App\Form\Search\AffiliateTool;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AdvancedToolSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pricing', ChoiceType::class, [
                'choices' => [
                    'Free' => 'free',
                    'Free Trial' => 'free-trial',
                    'Freemium' => 'freemium',
                    'Contact for Pricing' => 'contact-pricing',
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Text' => 'text',
                    'Image' => 'image',
                    'Code' => 'code',
                    'Audio' => 'audio',
                    'Video' => 'video',
                    'Business' => 'buisness',
                ],
                'expanded' => true,
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}