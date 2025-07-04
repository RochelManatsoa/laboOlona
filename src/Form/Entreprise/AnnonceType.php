<?php

namespace App\Form\Entreprise;

use App\Entity\Secteur;
use App\Entity\EntrepriseProfile;
use App\Entity\BusinessModel\Boost;
use App\Entity\Candidate\Competences;
use App\Entity\Entreprise\JobListing;
use Symfony\Component\Form\FormEvent;
use App\Entity\Moderateur\TypeContrat;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use App\Entity\BusinessModel\BoostFacebook;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\IsTrue;
use App\Form\DataTransformer\CompetencesTransformer;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class AnnonceType extends AbstractType
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
                'label' => 'Donnez un titre à votre annonce',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Le titre doit captiver l\'attention et refléter clairement le poste à pourvoir.',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le titre est obligatoires.'),
                ]),
            ])
            ->add('secteur', EntityType::class, [
                'label' => 'Sélectionnez le secteur d\'activité',
                'class' => Secteur::class,
                'attr' => [],
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'required' => false,
                'help' => 'Quel secteur d\'activité de votre entreprise sera sollicité pour cette mission ? ',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le secteur est obligatoires.'),
                ]),
            ])
            ->add('dateExpiration', DateType::class, [
                'required' => false,
                'label' => 'Date fin de candidature',
                'widget' => 'single_text',  
                'format' => 'yyyy-MM-dd',   
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Indiquez la date de début de la mission ou de l\'emploi.'
            ])
            ->add('entreprise', EntityType::class, [
                'required' => false,
                'class' => EntrepriseProfile::class,
                'label' => 'Selectionnez une entreprise',
                'attr' => []
            ])
            ->add('typeContrat', EntityType::class, [
                'class' => TypeContrat::class,
                'label' => 'Type de contrat',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Déterminez le type de contrat proposé pour cette position.',
                'attr' => []
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Décrivez les responsabilités et les tâches du poste.',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ],
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Fournissez une description détaillée des responsabilités et des tâches liées au poste.',
                'constraints' => new Sequentially([
                    new NotBlank(message:'La description est obligatoires.'),
                ]),
            ])
            ->add('boost', EntityType::class, [
                'class' => Boost::class,
                'choices' => $this->entityManager->getRepository(Boost::class)->findBy(['type' => 'JOB_LISTING']),
                'choice_label' => function ($boost) {
                    return $boost->getName(); 
                },
                'expanded' => true,  
                'required' => false, 
                'placeholder' => 'Pas de boost',
                'label' => false
            ])
            ->add('boostFacebook', EntityType::class, [
                'class' => BoostFacebook::class,
                'attr' => ['class' => 'boost-select radio-grid', 'data-html' => true],
                'choices' => $this->entityManager->getRepository(BoostFacebook::class)->findBy(['type' => 'OT_PLUS_FB']),
                'choice_label' => function ($boostFB) {
                    return $boostFB->getName().' ('.$boostFB->getCredit().' crédits)'; 
                },
                'expanded' => true,  
                'required' => false, 
                'placeholder' => 'Pas de boost',
                'label' => false
            ])
            ->add('budgetAnnonce', BudgetAnnonceType::class, [
                'label' => 'Budget prévu pour la mission',
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'Champ obligatoire.'),
                ]),
                'help' => 'Définissez le budget alloué pour cette annonce ou mission.'
            ])
            ->add('lieu', ChoiceType::class, [
                'choices' => [
                    'Remote' => 'Remote',
                    'Local' => 'Local',
                    'Télétravail' => 'Télétravail',
                    'Coworking Olona Talents' => 'Coworking Olona Talents',
                ],
                'required' => false,
                'label' => 'Lieu',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'Ce champ est obligatoires.'),
                ]),
                'help' => 'Indiquez le lieu de travail principal pour ce poste.'
            ])
            ->add('nombrePoste', null, [
                'label' => 'Nombre de personne à chercher',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'required' => false,
                'constraints' => new Sequentially([
                    new NotBlank(message:'Ce champ est obligatoires.'),
                ]),
                'help' => 'Précisez le nombre de postes disponibles.'
            ])
            ->add('competences', TextType::class, [
                'autocomplete' => true,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'required' => false,
                'constraints' => new Sequentially([
                    new NotBlank(message:'Ce champ est obligatoires.'),
                ]),
                'help' => 'Ajoutez les compétences clés requises pour le poste. Séparez-les par des virgules.',
                'attr' => [
                    'data-controller' => 'technical-add-autocomplete',
                    'palcehoder' => "Domaine d'expertise",
                ],
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                    'delimiter' => ',',
                ],
                'autocomplete_url' => '/autocomplete/competences_autocomplete_field' ,
                'no_results_found_text' => 'Aucun résultat' ,
                'no_more_results_text' => 'Plus de résultats' ,
            ])
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
