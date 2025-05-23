<?php

namespace App\Form\Search;

use App\Entity\Langue;
use App\Entity\Secteur;
use App\Entity\CandidateProfile;
use App\Data\SearchCandidateData;
use App\Repository\LangueRepository;
use App\Entity\Candidate\Competences;
use App\Repository\SecteurRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Repository\Candidate\CompetencesRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactoryInterface;

class SearchCandidateType extends AbstractType
{
    public function __construct(
        private CandidateProfileRepository $candidateProfileRepository,
        private CompetencesRepository $competencesRepository,
        private FormFactoryInterface $factory,
        private LangueRepository $langueRepository
    ){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('secteurs', EntityType::class, [
                'attr' => ['placeholder' => 'Secteur'],
                'query_builder' => function (SecteurRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->where('s.candidat IS NOT EMPTY');
                },
                'required' => false,
                'class' => Secteur::class,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('page')
            ; 

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();
            if (!$data instanceof SearchCandidateData) {
                $data = new SearchCandidateData();
            }
            $secteur = $data->getSecteurs() ?? [];
            $this->addTitreField($form, $secteur);

        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();
            $secteur = isset($data['secteurs']) ? $data['secteurs'] : [];
            // Si le secteur est défini, utilisez-le pour mettre à jour le champ "titre"
            if (!empty($secteur)) {
                $this->updateTitreChoices($form, $secteur);
            } else {
                // Sinon, ajoutez le champ "titre" avec les options par défaut
                $this->addTitreField($form, []);
            }
        });
    }

    private function addTitreField(FormInterface $form, $secteur)
    {
        $titres = $this->candidateProfileRepository->findUniqueTitlesBySecteurs($secteur);

        // Supprimer le champ "titre" existant s'il existe
        if ($form->has('titre')) {
            $form->remove('titre');
        }
        
        $form->add('titre', ChoiceType::class, [
            'choices' => array_combine($titres, $titres),
            'choice_label' => function ($choice, $key, $value) {
                return $value;
            },
            'attr' => [
                'placeholder' => 'Titre du poste default',
            ],
            'multiple' => true,
            'autocomplete' => true,
            'required' => false, // Vous pouvez rendre ce champ facultatif
        ]);

        $form->add('competences', ChoiceType::class, [
            'choices' => $secteur ? $this->competencesRepository->findCompetencesBySecteurs($secteur) : [],
            'choice_label' => 'nom',
            'placeholder' => 'Compétences',
            'multiple' => true,
            'mapped' => false,
            'required' => false, // Vous pouvez rendre ce champ facultatif
        ]);

        $form->add('langues', ChoiceType::class, [
            'choices' => $this->langueRepository->findAll(),
            'choice_label' => 'nom',
            'placeholder' => 'Langues',
            'multiple' => true,
            'autocomplete' => true,
            'mapped' => false,
            'required' => false, // Vous pouvez rendre ce champ facultatif
        ]);
    }

    private function updateTitreChoices(FormInterface $form, $secteur)
    {
        $titres = $this->candidateProfileRepository->findUniqueTitlesBySecteurs($secteur);

        // Supprimer le champ "titre" existant
        if ($form->has('titre')) {
            $form->remove('titre');
        }

        // Recréer le champ "titre" avec les nouvelles options
        $form->add('titre', ChoiceType::class, [
            'choices' => array_combine($titres, $titres),
            'choice_label' => function ($choice, $key, $value) {
                return $value;
            },
            'placeholder' => 'Titre du poste updated',
            'attr' => [
            ],
            'multiple' => true,
            // 'autocomplete' => true,
            'required' => false,
            // 'auto_initialize' => false, 
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchCandidateData::class,
            'method' => 'GET',
            'csrf_protection' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}

