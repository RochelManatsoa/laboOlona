<?php

namespace App\WhiteLabel\Form\Client1;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Doctrine\Persistence\ManagerRegistry;
use App\WhiteLabel\Entity\Client1\Secteur;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\WhiteLabel\Form\Client1\PrimeAnnonceType;
use App\WhiteLabel\Form\Client1\BudgetAnnonceType;
use Symfony\Component\Validator\Constraints\Length;
use App\WhiteLabel\Entity\Client1\EntrepriseProfile;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\WhiteLabel\Entity\Client1\Candidate\Competences;
use App\WhiteLabel\Entity\Client1\Entreprise\JobListing;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use App\WhiteLabel\Form\Client1\DataTransformer\CompetencesTransformer;

class JobListing1Type extends AbstractType
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private CompetencesTransformer $competencesTransformer,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $sluggerInterface,
    ) {
        $this->entityManager = $managerRegistry->getManager('client1');
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
            ->add('imageFile', FileType::class, [
                'required' => false,
                'label' => 'Image',
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
            ->add('salaire', ChoiceType::class, [
                'choices' => [
                    'Diana' => 'Diana',
                    'Sava' => 'Sava',
                    'Itasy' => 'Itasy',
                    'Analamanga' => 'Analamanga',
                    'Vakinankaratra' => 'Vakinankaratra',
                    'Bongolava' => 'Bongolava',
                    'Sofia' => 'Sofia',
                    'Boeny' => 'Boeny',
                    'Betsiboka' => 'Betsiboka',
                    'Melaky' => 'Melaky',
                    'Alaotra-Mangoro' => 'Alaotra-Mangoro',
                    'Atsinanana' => 'Atsinanana',
                    'Analanjirofo' => 'Analanjirofo',
                    "Amoron'i Mania" => "Amoron'i Mania",
                    'Haute Matsiatra' => 'Haute Matsiatra',
                    'Vatovavy-Fitovinany' => 'Vatovavy-Fitovinany',
                    'Atsimo-Atsinanana' => 'Atsimo-Atsinanana',
                    'Ihorombe' => 'Ihorombe',
                    'Menabe' => 'Menabe',
                    'Atsimo-Andrefana' => 'Atsimo-Andrefana',
                    'Androy' => 'Androy',
                    'Anosy' => 'Anosy',
                ],
                'required' => false,
                'mapped' => false,
                'label' => 'Région',
                'placeholder' => 'Sélectionnez une région',
                'label_attr' => [
                    'class' => 'fw-bold fs-6'
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'Ce champ est obligatoires.'),
                ]),
                'help' => 'Choisissez une région pour voir les agences disponibles.'
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
            ->add('primeAnnonce', \App\WhiteLabel\Form\Client1\PrimeAnnonceType::class, [
                'label' => 'Prime de cooptation',
                'default_devise' => $options['default_devise'] ?? null
            ])
            ->add('budgetAnnonce', \App\WhiteLabel\Form\Client1\BudgetAnnonceType::class, [
                'label' => 'Salaire',
                'default_devise' => $options['default_devise'] ?? null
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
