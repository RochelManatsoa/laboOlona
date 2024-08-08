<?php

namespace App\Form\Candidate;

use App\Entity\Prestation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use App\Form\DataTransformer\JsonToArrayTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PrestationType extends AbstractType
{
    private $transformer;

    public function __construct(JsonToArrayTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add(
                $builder->create('competencesRequises', TextType::class, [
                    'required' => false,
                    'attr' => [
                        'data-role' => 'tagify',
                    ]
                ])->addModelTransformer($this->transformer)
            )
            ->add('modalitesPrestation', ChoiceType::class, [
                'choices' => Prestation::CHOICE_MODALITE
            ])
            ->add(
                $builder->create('specialisations', TextType::class, [
                    'required' => false,
                    'attr' => [
                        'data-role' => 'tagify',
                    ]
                ])->addModelTransformer($this->transformer)
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Prestation::class,
        ]);
    }
}