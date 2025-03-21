<?php

namespace App\Form\Entreprise;

use App\Entity\Secteur;
use App\Entity\Candidate\Competences;
use App\Entity\Entreprise\JobListing;
use Symfony\Component\Form\FormEvent;
use App\Entity\Moderateur\TypeContrat;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use App\Form\DataTransformer\CompetencesTransformer;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class JobListingType extends AbstractType
{
    public function __construct(
        private CompetencesTransformer $competencesTransformer,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $sluggerInterface,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => false,
                'label' => 'Titre (*)',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le titre est obligatoire.'),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Le titre est trop court',
                        maxMessage: 'Le titre ne doit pas depasser 100 characters',
                    ),
                ]),
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Entrez un titre clair et concis pour la prestation (maximum 100 caractères).',
            ])
            ->add('secteur', EntityType::class, [
                'class' => Secteur::class,
                'label' => 'Secteur d\'activité (*)',
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
                'help' => 'Sélectionnez le secteur d\'activité pour cette offre d\'emploi.',
            ])
            ->add('dateExpiration', DateType::class, [
                'label' => 'Date d\'expiration',
                'widget' => 'single_text',  
                'format' => 'yyyy-MM-dd',   
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Date d\'expiration de l\'offre d\'emploi.',
            ])
            ->add('typeContrat', EntityType::class, [
                'class' => TypeContrat::class,
                'label' => 'Type de contrat',
                'attr' => [],
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Sélectionnez le type de contrat pour cette offre d\'emploi.',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description (*)',
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
                'help' => 'Décrivez en détail l\'offre d\'emploi. Cela aidera les utilisateurs à comprendre ce que vous proposez.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            // ->add('salaire', MoneyType::class, ['label' => false])
            // ->add('prime', MoneyType::class, ['label' => false])
            ->add('primeAnnonce', PrimeAnnonceType::class, [
                'label' => false,
                'default_devise' => $options['default_devise'] ?? null
            ])
            ->add('budgetAnnonce', BudgetAnnonceType::class, [
                'label' => false,
                'default_devise' => $options['default_devise'] ?? null
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => [],
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Lieu de travail pour cette offre d\'emploi.',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => JobListing::getStatuses(),
                'label' => false,
            ])
            ->add('nombrePoste', null, [
                'label' => 'Nombre de poste',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Nombre de poste à pourvoir',
            ])
            ->add('competences', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Vous devriez choisir un compétence.',
                    ]),
                ],
                'label' => 'Compétences requises (*)',
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
            // ->add('langues')
        ;

        $builder->get('competences')
            ->addModelTransformer($this->competencesTransformer)
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
        
            // récupérer la valeur du champ "aicores" depuis le formulaire
            $competencesDataValue = $form->get('competences')->getNormData();
            
            // diviser la chaîne en tableau
            $skillValues = explode(',', $competencesDataValue);
            
                // trier les valeurs en IDs et chaînes de caractères
                list($skillsIds, $skillsStrings) = $this->sortValue($skillValues);
        
            // vider la collection originale
            foreach ($data->getcompetences() as $existingSkill) {
                $data->removeCompetence($existingSkill);
            }
        
            // ajouter les nouvelles entités à partir des IDs
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
            'data_class' => JobListing::class,
            'default_devise' => null, 
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