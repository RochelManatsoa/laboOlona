<?php

namespace App\Form\Moderateur\Profile;

use App\Data\Profile\CandidatSearchData;
use App\Entity\CandidateProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class CandidatSearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('q', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nom ou prénom ou email'
                ]
            ])
            ->add('matricule', IntegerType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => '0123'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => CandidateProfile::getStatuses(),
                'label' => false,
                'required' => false,
                'placeholder' => 'Status',
                'attr' => [
                ]
            ])
            ->add('cv', ChoiceType::class, [
                'choices' => [
                    'Sans CV' => 0,
                    'Avec CV' => 1,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'CV',
            ])
            ->add('tarif', ChoiceType::class, [
                'choices' => [
                    'Sans Tarif' => 0,
                    'Avec Tarif' => 1,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Tarif',
            ])
            ->add('level', ChoiceType::class, [
                'choices' => [
                    'Level 0' => 0,
                    'Level 1' => 1,
                    'Level 2' => 2,
                    'Level 3' => 3,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Level',
            ])
            ->add('relance', ChoiceType::class, [
                'choices' => [
                    'Sans Relance' => 0,
                    'Avec Relance' => 1,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Relance',
            ])
            ->add('dispo', ChoiceType::class, [
                'choices' => [
                    'Sans Disponibilité' => 0,
                    'Avec Disponibilité' => 1,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Disponibilité',
            ])
            ->add('resume', ChoiceType::class, [
                'choices' => [
                    'Sans Biographie' => 0,
                    'Avec Biographie' => 1,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Biographie',
            ])
            ->add('secteurs', ChoiceType::class, [
                'choices' => [
                    'Sans Secteur' => 0,
                    'Avec Secteur' => 1,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Secteur',
            ])
            ->add('experiences', ChoiceType::class, [
                'choices' => [
                    'Sans expériences' => 0,
                    'Avec expériences' => 1,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Expériences',
            ])
            ->add('competences', ChoiceType::class, [
                'choices' => [
                    'Sans competénces' => 0,
                    'Avec competénces' => 1,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Compétences',
            ])
            ->add('avatar', ChoiceType::class, [
                'choices' => [
                    'Sans photo' => 0,
                    'Avec photo' => 1,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Photo',
            ])
            ->add('province', ChoiceType::class, [
                'choices' => [
                    'Sans province' => 0,
                    'Avec province' => 1,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Province',
            ])
            ->add('region', ChoiceType::class, [
                'choices' => [
                    'Sans region' => 0,
                    'Avec region' => 1,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Région',
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'Sans genre' => 0,
                    'Avec genre' => 1,
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Genre',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CandidatSearchData::class,
            'method' => 'GET',
            'csrf_protection' => false
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
