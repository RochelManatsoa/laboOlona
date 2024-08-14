<?php

namespace App\Form\Entreprise;

use App\Entity\Secteur;
use App\Entity\EntrepriseProfile;
use App\Entity\Candidate\Competences;
use App\Entity\Entreprise\JobListing;
use Symfony\Component\Form\FormEvent;
use App\Entity\Moderateur\TypeContrat;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\IsTrue;
use App\Form\DataTransformer\CompetencesTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
            ->add('titre', TextType::class, ['label' => 'Donnez un titre à votre annonce',])
            ->add('secteur', EntityType::class, [
                'label' => 'app_dashboard_entreprise_posting_new.sector',
                'class' => Secteur::class,
                'attr' => []
            ])
            ->add('dateExpiration', DateType::class, [
                'label' => 'app_dashboard_entreprise_posting_new.planned_date',
                'widget' => 'single_text',  
                'format' => 'yyyy-MM-dd',   
            ])
            ->add('entreprise', EntityType::class, [
                'class' => EntrepriseProfile::class,
                'label' => 'Selectionnez une entreprise',
                'attr' => []
            ])
            ->add('typeContrat', EntityType::class, [
                'class' => TypeContrat::class,
                'label' => 'app_dashboard_entreprise_posting_new.type',
                'attr' => []
            ])
            ->add('description', TextareaType::class, [
                'label' => 'app_dashboard_entreprise_posting_new.desc_form',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('salaire', HiddenType::class, [])
            ->add('budgetAnnonce', BudgetAnnonceType::class, [
                'label' => 'Budget',
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
            ->add('lieu', TextType::class, ['label' => 'Lieu',])
            ->add('nombrePoste', null, ['label' => 'Nombre de personne à chercher',])
            ->add('competences', TextType::class, [
                'autocomplete' => true,
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
