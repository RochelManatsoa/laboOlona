<?php

namespace App\Form;

use App\Entity\Prestation;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\DataTransformer\JsonToArrayTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PrestationStaffaType extends AbstractType
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
            ->add('tarifsProposes')
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
            ->add('medias')
            // ->add('evaluations')
            ->add('disponibilites')
            ->add('status', ChoiceType::class, [
                'choices' => Prestation::CHOICE_STATUS
            ])
            ->add('cleanDescription', TextareaType::class, [
                'required' => false,
            ])
            ->add('openai', TextareaType::class, [
                'required' => false,
            ])
            ->add('candidateProfile', EntityType::class, [
                'class' => CandidateProfile::class,
                'choice_label' => function(?CandidateProfile $candidateProfile) {
                    return $candidateProfile ? $candidateProfile->getMatricule() : '';
                },
                'placeholder' => 'Choisir un profil', // Vous pouvez personnaliser le texte ici
                'required' => false,
            ])
            ->add('entrepriseProfile', EntityType::class, [
                'class' => EntrepriseProfile::class,
                'choice_label' => function(?EntrepriseProfile $entrepriseProfile) {
                    return $entrepriseProfile ? $entrepriseProfile->getNom() : '';
                },
                'placeholder' => 'Choisir une entreprise', // Vous pouvez personnaliser le texte ici
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Prestation::class,
        ]);
    }
}
