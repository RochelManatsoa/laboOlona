<?php

namespace App\WhiteLabel\Form\Client1;

use App\WhiteLabel\Entity\Client1\Secteur;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use App\WhiteLabel\Entity\Client1\Candidate\Competences;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use App\Form\Entreprise\BudgetAnnonceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use App\Form\DataTransformer\CompetencesTransformer;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class JobListing1Type extends AbstractType
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
            ->add('dateExpiration', DateType::class, [
                'label' => 'Date d\'expiration',
                'widget' => 'single_text',  
                'format' => 'yyyy-MM-dd',   
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Date d\'expiration de l\'offre d\'emploi.',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut de l\'offre d\'emploi',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'choices' => JobListing::getStatuses(),
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
            ->add('entreprise', EntityType::class, [
                'required' => false,
                'class' => EntrepriseProfile::class,
                'label' => 'Selectionnez une entreprise',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => []
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
            // ->add('boost', EntityType::class, [
            //     'class' => Boost::class,
            //     'choice_label' => 'id',
            // ])
            // ->add('boostFacebook', EntityType::class, [
            //     'class' => BoostFacebook::class,
            //     'choice_label' => 'id',
            // ])
        ;
        $builder->get('competences')
            ->addModelTransformer($this->competencesTransformer)
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
        
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
