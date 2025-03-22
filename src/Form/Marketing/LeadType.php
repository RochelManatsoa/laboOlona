<?php

namespace App\Form\Marketing;

use App\Entity\User;
use App\Entity\Marketing\Lead;
use App\Entity\Marketing\Source;
use Symfony\Component\Form\AbstractType;
use App\Form\Autocomplete\UserAutocompleteField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class LeadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', UserAutocompleteField::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Séléctionnez un utilisateur pour le lead.',
            ])
            ->add('fullName', TextType::class, [
                'required' => false,
                'label' => 'Nom complet',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Nom complet.',
            ])
            ->add('email', TextType::class, [
                'required' => false,
                'label' => 'Email',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Email.',
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'Téléphone',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Téléphone.',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Lead::getStatuses(),
                'required' => false,
                'label' => 'Status',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Status du lead.',
            ])
            ->add('source', EntityType::class, [
                'class' => Source::class,
                'choice_label' => 'name',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Sélectionnez une source pour le lead.',
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'label' => 'Commentaire',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Notes pour le lead.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lead::class,
        ]);
    }
}
