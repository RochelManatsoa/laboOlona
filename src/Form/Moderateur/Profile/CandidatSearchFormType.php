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
    
    public function getBlockPrefix()
    {
        return '';
    }
}
