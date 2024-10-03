<?php

namespace App\Form\V2;

use App\Entity\Secteur;
use App\Entity\CandidateProfile;
use App\Form\Candidat\TarifCandidatType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CandidateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('secteurs', EntityType::class, [
                'required' => false,
                'class' => Secteur::class,
                'label' => 'Secteur d\'expertise *',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Sélectionnez un ou plusieurs secteurs correspondant à vos compétences professionnelles.',
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre professionnel *',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'required' => false,
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le titre professionnel est obligatoire.'),
                ]),
                'help' => 'Indiquez votre titre ou position actuelle, par exemple : "Développeur Web Senior".',
            ])
            ->add('localisation', CountryType::class, [
                'required' => false,
                'label' => 'Pays de résidence *',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'placeholder' => 'Sélectionner votre pays', 
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le pays est obligatoire.'),
                ]),
                'help' => 'Choisissez le pays où vous résidez actuellement.',
            ])
            ->add('tarifCandidat', TarifCandidatType::class, [
                'label' => 'Votre tarif *',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'required' => false,
                'help' => 'Indiquez votre tarif horaire ou journalier en fonction de vos services.',
            ])
            ->add('resume', TextareaType::class, [
                'label' => 'Parlez nous de vous *',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'required' => false ,
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'La biographie est obligatoire.'),
                ]),
                'help' => 'Décrivez brièvement votre parcours professionnel et vos compétences.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CandidateProfile::class,
        ]);
    }
}
