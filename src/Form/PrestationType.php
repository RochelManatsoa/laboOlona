<?php

namespace App\Form;

use App\Entity\Secteur;
use App\Entity\Prestation;
use App\Entity\BusinessModel\Boost;
use App\Entity\Candidate\Competences;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use App\Entity\Prestation\TypePrestation;
use App\Form\Prestation\AvailabilityType;
use App\Form\Prestation\TarifPrestationType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use App\Form\DataTransformer\CompetencesTransformer;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PrestationType extends AbstractType
{
    public function __construct(
        private CompetencesTransformer $competencesTransformer,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $sluggerInterface,
    ) {}
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => false,
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le titre est obligatoire.'),
                    new Length(
                        min: 2,
                        max: 50,
                        minMessage: 'Le titre est trop court',
                        maxMessage: 'Le titre ne doit pas depasser 50 characters',
                    ),
                ]),
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Entrez un titre clair et concis pour la prestation (2 à 50 caractères).',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'constraints' => new Sequentially([
                    new NotBlank(message:'La description est obligatoire.'),
                    new Length(
                        min: 3,
                        minMessage: 'La description est trop court',
                    ),
                ]),
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Décrivez en détail la prestation. Cela aidera les utilisateurs à comprendre ce que vous proposez.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('boost', EntityType::class, [
                'class' => Boost::class,
                'attr' => ['class' => 'boost-select', 'data-html' => true],
                'choices' => $this->entityManager->getRepository(Boost::class)->findBy(['type' => $options['boostType']]),
                'choice_label' => function ($boost) {
                    return $boost->getName().' ('.$boost->getCredit().' crédits)'; 
                },
                'choice_attr' => function($boost) {
                    return ['data-content' => $boost->getDescription()];
                },
                'expanded' => true,  
                'required' => false, 
                'placeholder' => 'Pas de boost',
                'label' => false,
                'help' => 'Choisissez un boost pour augmenter la visibilité de votre prestation (optionnel).',
            ])
            ->add('tarifPrestation', TarifPrestationType::class, [
                'required' => false,
                'label' => 'Tarif proposé',
                'help' => 'Entrez le tarif que vous proposez pour cette prestation.',
            ])
            ->add('modalitesPrestation', ChoiceType::class, [
                'choices' => Prestation::CHOICE_MODALITE,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Sélectionnez les modalités de prestation (en présentiel, à distance, etc.).',
            ])
            ->add('specialisations', null, [
                'help' => 'Indiquez les spécialités liées à cette prestation.',
            ])
            ->add('medias', null, [
                'help' => 'Ajoutez des médias ou fichiers pour illustrer votre prestation (images, documents, etc.).',
            ])
            ->add('evaluations', null, [
                'help' => 'Ajoutez les évaluations liées à cette prestation, si disponible.',
            ])
            ->add('disponibilites', null, [
                'help' => 'Indiquez vos disponibilités pour cette prestation.',
            ])
            ->add('availability', AvailabilityType::class, [
                'required' => false,
                'label' => 'Disponibilité',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Choisissez les jours et heures où vous êtes disponible pour cette prestation.',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Prestation::CHOICE_STATUS
            ])
            ->add('motsCles', TextareaType::class, [
                'required' => false, 
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Ajoutez des mots-clés pour améliorer la recherche de votre prestation.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('typePrestation', EntityType::class, [
                'class' => TypePrestation::class,
                'choice_label' => 'name',
                'label' => 'Type de service',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Sélectionnez le type de prestation que vous proposez.',
                'autocomplete' => true,
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('portfolioLinks', TextType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Ajoutez un lien vers votre portfolio ou exemples de projets (optionnel).',
            ])
            ->add('temoignages')
            ->add('contactTelephone', TextType::class, [
                'required' => false,
                'label' => 'Téléphone (*)',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le contact est obligatoire.'),
                ]),
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Numéro de téléphone où vous pouvez être contacté.',
            ])
            ->add('contactEmail', EmailType::class, [
                'required' => false,
                'label' => 'Mail de contact (*)',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le mail est obligatoire.'),
                ]),
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Entrez une adresse e-mail pour vous contacter.',
            ])
            ->add('contactReseauxSociaux', TextType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Entrez des liens vers vos profils sur les réseaux sociaux (LinkedIn, Facebook, etc.).',
            ])
            ->add('preferencesCommunication', TextType::class, [
                'required' => false,
                'label' => 'Préférence de communication (*)',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Champ obligatoire.'),
                ]),
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Précisez vos préférences pour être contacté (par téléphone, e-mail, etc.).',
            ])
            ->add('conditionsParticulieres', TextareaType::class, [
                'required' => false, 
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ],
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Indiquez les conditions spécifiques pour cette prestation (si applicable).',
            ])
            ->add('engagementQualite', TextareaType::class, [
                'required' => false, 
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ],
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Décrivez vos engagements qualité pour cette prestation.',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => false,
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devriez accepter nos conditions.',
                    ]),
                ],
                'attr' => [
                    'label' => 'J\'accepte les termes et conditions.',
                ],
            ])
            ->add('competences', TextType::class, [
                'label' => 'Spécialisations',
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
                'help' => 'Indiquez vos compétences et spécialisations pour cette prestation.',
            ])
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'choice_label' => function(?Secteur $secteur) {
                    return $secteur ? $secteur->getNom() : '';
                },
                'placeholder' => 'Choisir un secteur', 
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Vous devriez choisir un secteur.',
                    ]),
                ],
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Sélectionnez le secteur d\'activité pour cette prestation.',
            ])
            ->add('file', FileType::class, [
                'required' => false,
                'label' => 'app_identity_expert_step_one.avatar_desc',
                'attr' => ['class' => 'd-none'],
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
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Téléchargez une image ou un fichier (JPEG, PNG, BMP). Taille maximale : 2 Mo.',
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
            'boostType' => "",
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
