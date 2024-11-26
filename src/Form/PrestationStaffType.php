<?php

namespace App\Form;

use App\Entity\Secteur;
use App\Entity\Prestation;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\Candidate\Competences;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Entity\Prestation\TypePrestation;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use App\Form\Prestation\AvailabilityType;
use App\Form\Prestation\TarifPrestationType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use App\Form\DataTransformer\CompetencesTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PrestationStaffType extends AbstractType
{
    public function __construct(
        private CompetencesTransformer $competencesTransformer,
        private EntityManagerInterface $entityManager,
    ) {}
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le titre est obligatoire.'),
                    new Length(
                        min: 2,
                        max: 50,
                        minMessage: 'Le titre est trop court',
                        maxMessage: 'Le titre ne doit pas depasser 50 characters',
                    ),
                ]),
            ])
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'choice_label' => function(?Secteur $secteur) {
                    return $secteur ? $secteur->getNom() : '';
                },
                'placeholder' => 'Choisir un secteur', 
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Prestation::CHOICE_STATUS,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'La description est obligatoire.'),
                    new Length(
                        min: 3,
                        minMessage: 'La description est trop court',
                    ),
                ]),
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('tarifPrestation', TarifPrestationType::class, [
                'required' => false,
                'label' => 'Tarif proposé',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('modalitesPrestation', ChoiceType::class, [
                'choices' => Prestation::CHOICE_MODALITE,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('availability', AvailabilityType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('openai', TextareaType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ],
            ])
            ->add('motsCles', TextareaType::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('typePrestation', EntityType::class, [
                'class' => TypePrestation::class,
                'choice_label' => 'name',
                'label' => 'Type de service',
                'autocomplete' => true,
                'expanded' => false,
                'multiple' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('portfolioLinks', TextareaType::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ],
            ])
            ->add('contactTelephone', TextType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le contact est obligatoire.'),
                ]),
            ])
            ->add('contactEmail', EmailType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le mail est obligatoire.'),
                ]),
            ])
            ->add('contactReseauxSociaux', TextareaType::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ],
            ])
            ->add('preferencesCommunication', TextType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'Champ obligatoire.'),
                ]),
            ])
            ->add('conditionsParticulieres', TextareaType::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ],
            ])
            ->add('engagementQualite', TextareaType::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ],
            ])
            ->add('candidateProfile', EntityType::class, [
                'class' => CandidateProfile::class,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'choice_label' => function(?CandidateProfile $candidateProfile) {
                    return $candidateProfile ? $candidateProfile->getMatricule() : '';
                },
                'placeholder' => 'Choisir un profil', 
                'required' => false,
            ])
            ->add('entrepriseProfile', EntityType::class, [
                'class' => EntrepriseProfile::class,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'choice_label' => function(?EntrepriseProfile $entrepriseProfile) {
                    return $entrepriseProfile ? $entrepriseProfile->getNom() : '';
                },
                'placeholder' => 'Choisir une entreprise', 
                'required' => false,
            ])
            ->add('competences', TextType::class, [
                'label' => "Compétences",
                'autocomplete' => true,
                'attr' => [
                    'data-controller' => 'technical-add-autocomplete',
                    'placeholder' => " ", 
                    'class' => 'form-control tom-select-custom' 
                ],
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                    'delimiter' => ',',
                    'classNames' => [
                        'control' => 'form-control',    // Appliquer class 'form-control' de Bootstrap au champ de saisie
                        'dropdown' => 'dropdown-menu',  // Appliquer class 'dropdown-menu' de Bootstrap au menu déroulant
                        'option' => 'dropdown-item',    // Appliquer class 'dropdown-item' de Bootstrap à chaque option
                    ],
                ],
                'autocomplete_url' => '/autocomplete/competences_autocomplete_field',
                'no_results_found_text' => 'Aucun résultat',
                'no_more_results_text' => 'Plus de résultats',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('file', FileType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'label' => 'Ajouter un fichier',
                'attr' => ['class' => ''],
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'image/bmp',
                        ],
                    ])
                ],
            ])
            ->add('isGenerated', CheckboxType::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'label' => 'Contenu regénéré ?'
            ])
        ;

        $builder->get('competences')
            ->addModelTransformer($this->competencesTransformer)
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            $competencesDataValue = $form->get('competences')->getNormData();
            $skillValues = explode(',', $competencesDataValue);
            list($skillsIds, $skillsStrings) = $this->sortValue($skillValues);
        
            foreach ($data->getcompetences() as $existingSkill) {
                $data->removeCompetence($existingSkill);
            }
        
            foreach ($skillsIds as $id) {
                $skill = $this->entityManager->getRepository(Competences::class)->find($id);
                if ($skill !== null) {
                    $data->addCompetence($skill);
                }
            }
        
            // créer et ajouter de nouvelles entités à partir des chaînes
            foreach ($skillsStrings as $string) {
                $skill = $this->entityManager->getRepository(Competences::class)->findOneBy([
                    'nom' => $string
                ]);
                if ($skill !== null) {
                    $data->addCompetence($skill);
                }
            }
        
            // mettre à jour les données de l'événement
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Prestation::class,
        ]);
    }

    private function sortValue($values)
    {
        $ids = [];
        $strings = [];
        foreach ($values as $value) {
            if (is_numeric($value)) {
                $ids[] = (int) $value;
            } else {
                $strings[] = $value;
            }
        }
        return [$ids, $strings];
    }
}
