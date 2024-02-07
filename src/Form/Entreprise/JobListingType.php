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
use App\Form\DataTransformer\CompetencesTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
            ->add('titre', TextType::class, ['label' => 'Titre',])
            ->add('secteur', EntityType::class, [
                'label' => false,
                'class' => Secteur::class,
                'attr' => []
            ])
            ->add('dateExpiration', DateType::class, [
                'label' => 'Expire le',
                'widget' => 'single_text',  
                'format' => 'yyyy-MM-dd',   
            ])
            ->add('typeContrat', EntityType::class, [
                'class' => TypeContrat::class,
                'label' => 'Type de contrat',
                'attr' => []
            ])
            ->add('description', TextareaType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'rows' => 8
                ]
            ])
            ->add('salaire', MoneyType::class, ['label' => false])
            ->add('prime', MoneyType::class, ['label' => false])
            ->add('lieu', TextType::class, ['label' => false ])
            ->add('status', ChoiceType::class, [
                'choices' => JobListing::getStatuses(),
                'label' => false,
            ])
            ->add('nombrePoste', null, ['label' => false])
            ->add('competences', TextType::class, [
                'label' => false,
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
            dump($competencesDataValue);
            
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