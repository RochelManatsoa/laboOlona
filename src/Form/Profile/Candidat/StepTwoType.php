<?php

namespace App\Form\Profile\Candidat;

use App\Entity\Secteur;
use App\Entity\CandidateProfile;
use App\Form\Candidat\TarifCandidatType;
use Symfony\Component\Form\AbstractType;
use App\Form\Profile\Candidat\ExperiencesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\NotBlank;

class StepTwoType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultSecteur = $this->em->getRepository(Secteur::class)->find(1);
        $builder
            ->add('titre', TextType::class, [
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.']),
                'label' => 'Votre titre *',
                'attr' => [
                    'placeholder' => 'Développeur application web'
                ],
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
            ])
            ->add('tarifCandidat', TarifCandidatType::class, [
                'required' => false,
                'label' => 'Tarif *',
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
            ])
            ->add('resume', TextareaType::class, [
                'label' => 'app_identity_expert.aspiration',
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
                'required' => false,
                'attr' => [
                    'rows' => 8,
                    'placeholder' => 'Passionné par le développement web et fort de plus de 5 ans d\'expérience...'
                ]
            ])
            ->add('cv', FileType::class, [
                'label' => 'app_identity_expert.cv',
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'custom-file-input'],
                'constraints' => [
                    new File([
                        'maxSize' => '4096k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un document PDF valide',
                    ])
                ],
            ])
            ->add('competences', CollectionType::class, [
                'entry_type' => CompetencesType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'by_reference' => false,
            ])
            ->add('experiences', CollectionType::class, [
                'entry_type' => ExperiencesType::class,
                'entry_options' => ['label' => true],
                'allow_add' => true,
                'by_reference' => false,
            ])
            ->add('langages', CollectionType::class, [
                'entry_type' => LangagesType::class,
                'entry_options' => ['label' => true],
                'allow_add' => true,
                'by_reference' => false,
            ])
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'label' => 'Secteur d\'expertise *',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'required' => true,
                'data' => [$defaultSecteur],
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
