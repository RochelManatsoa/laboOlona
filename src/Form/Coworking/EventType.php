<?php

namespace App\Form\Coworking;

use Doctrine\ORM\QueryBuilder;
use App\Entity\Coworking\Event;
use App\Entity\Coworking\Place;
use App\Entity\Coworking\Product;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use App\Form\Autocomplete\UserAutocompleteField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['is_admin']) {
            $builder
                ->add('user', UserAutocompleteField::class, [])
                ->add('places', EntityType::class, [
                    'class' => Place::class,
                    'choice_label' => 'name',
                    'label' => 'Place (*)',
                    'label_attr' => [
                        'class' => 'fw-bold fs-5' 
                    ],
                    'help' => 'Place où se déroule la réservation.',
                    'autocomplete' => true,
                    'multiple' => true,  
                    'expanded' => false 
                ])
            ;
        }

        $builder
            ->add('duration', ChoiceType::class, [
                'choices' => [
                    'Journée' => 'journee',
                    'Demi journée' => 'demi_journee',
                ],
                'label' => 'Durée',
                'expanded' => true,
                'multiple' => false,
                'help' => 'Choisissez la durée de la réservation.',
            ])
            ->add('startEvent', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'placeholder' => [
                    'year' => 'Année', 'month' => 'Mois', 'day' => 'Jour', 'hour' => 'Heure', 'minute' => 'Minute'
                ],
                'required' => false,
                'attr' => ['class' => 'date-picker'],
                'label' => 'Date de reservation (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Date et heure de la réservation.',
            ])
            ->add('boissons', EntityType::class, [
                'class' => Product::class,
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('p')
                        ->where('p.category = :category')
                        ->setParameter('category', 'boissons');
                },
                'choice_label' => 'name',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'expanded' => true,    
                'label' => 'Boissons',
                'help' => 'Ajouter une boisson à votre réservation.',
                'placeholder' => 'Choisissez une boisson',
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);

            if ($options['is_admin']) {
                $builder
                    ->add('description', TextareaType::class, [
                        'required' => false,
                        'label' => 'Description',
                        'label_attr' => [
                            'class' => 'fw-bold fs-5' 
                        ],
                        'help' => 'Notes pour la réservation. Ces notes seront visibles par les utilisateurs qui ont accès à la réservation.',
                        'attr' => [
                            'rows' => 6,
                            'class' => 'ckeditor-textarea'
                        ]
                    ])
                    ->add('backgroundColor', ColorType::class, [
                        'label' => 'Couleur de fond',
                        'label_attr' => ['class' => 'fw-bold fs-5'],
                        'attr' => ['class' => 'color-picker', 'value' => '#ff0000'],
                    ])
                    ->add('borderColor', ColorType::class, [
                        'label' => 'Couleur de la bordure',
                        'label_attr' => ['class' => 'fw-bold fs-5'],
                        'attr' => ['class' => 'color-picker', 'value' => '#00ff00'],
                    ])
                    ->add('textColor', ColorType::class, [
                        'label' => 'Couleur du texte',
                        'label_attr' => ['class' => 'fw-bold fs-5'],
                        'attr' => ['class' => 'color-picker', 'value' => '#000000'],
                    ])
                    // ->add('allDay')
                ;
            }

        // Add event listener to set endEvent based on duration
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $eventData = $event->getData();

            if ($eventData instanceof Event) {
                if ($eventData->getDuration() === 'demi_journee') {
                    $eventData->setEndEvent((clone $eventData->getStartEvent())->modify('+4 hours'));
                } elseif ($eventData->getDuration() === 'journee') {
                    $eventData->setEndEvent((clone $eventData->getStartEvent())->modify('+8 hours'));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'is_admin' => false,
        ]);
    }
}
