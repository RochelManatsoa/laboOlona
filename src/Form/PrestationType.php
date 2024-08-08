<?php

namespace App\Form;

use App\Entity\Secteur;
use App\Entity\Prestation;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\Candidate\Competences;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use App\Form\Prestation\AvailabilityType;
use App\Form\Prestation\TarifPrestationType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\IsTrue;
use App\Form\DataTransformer\CompetencesTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
                'required' => true,
            ])
            ->add('description', HiddenType::class, [
                'required' => false,
            ])
            // ->add('competencesRequises')
            // ->add('tarifsProposes')
            ->add('tarifPrestation', TarifPrestationType::class, [
                'required' => false,
                'label' => 'Tarif proposé',
            ])
            ->add('modalitesPrestation', ChoiceType::class, [
                'choices' => Prestation::CHOICE_MODALITE
            ])
            ->add('specialisations')
            ->add('medias')
            ->add('evaluations')
            ->add('disponibilites')
            ->add('availability', AvailabilityType::class, [
                'required' => false,
                'label' => 'Disponibilité',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Prestation::CHOICE_STATUS
            ])
            ->add('motsCles', TextareaType::class, [
                'attr' => [
                    'placeholder' => '',
                    'rows' => 2,
                ],
            ])
            ->add('typeService')
            ->add('portfolioLinks', TextType::class, [
                'required' => false,
            ])
            ->add('temoignages')
            ->add('contactTelephone', TextType::class, [
                'required' => false,
            ])
            ->add('contactEmail', EmailType::class, [
                'required' => false,
            ])
            ->add('contactReseauxSociaux', TextType::class, [
                'required' => false,
            ])
            ->add('preferencesCommunication')
            ->add('conditionsParticulieres', TextareaType::class, [
                'attr' => [
                    'placeholder' => '',
                    'rows' =>   5,
                ],
            ])
            ->add('engagementQualite', TextareaType::class, [
                'attr' => [
                    'placeholder' => '',
                    'rows' => 5,
                ],
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
                'label' => false,
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
            ])
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'choice_label' => function(?Secteur $secteur) {
                    return $secteur ? $secteur->getNom() : '';
                },
                'placeholder' => 'Choisir un secteur', 
                'required' => false,
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
