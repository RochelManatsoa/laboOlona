<?php

namespace App\Form\Search;

use App\Entity\Secteur;
use App\Data\SearchCandidateData;
use App\Entity\Candidate\Competences;
use App\Entity\CandidateProfile;
use App\Entity\Langue;
use App\Repository\LangueRepository;
use App\Repository\SecteurRepository;
use Symfony\Component\Form\AbstractType;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Repository\Candidate\CompetencesRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactoryInterface;

class SearchCandidateTypeCopy extends AbstractType
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
            ->add('titre', EntityType::class, [
                'class' => CandidateProfile::class,
                'choice_label' => 'titre', // Assurez-vous que 'titre' est un attribut valide de CandidateProfile
                'attr' => [
                    'class' => 'js-example-basic-multiple',
                    'placeholder' => 'Titre du poste',
                ],
                'multiple' => true,
                'autocomplete' => true,
                'required' => false,
            ])
            ->add('competences', EntityType::class, [
                'choices' => $this->competencesRepository->findAll(),
                'class' => Competences::class,
                'choice_label' => 'nom',
                'attr' => [
                    'class' => 'js-example-basic-multiple',
                    'placeholder' => 'Compétences',
                ],
                'autocomplete' => true,
                'multiple' => true,
                'mapped' => false,
                'required' => false, // Vous pouvez rendre ce champ facultatif
            ])
            ->add('langue', EntityType::class, [
                'choices' => $this->langueRepository->findAll(),
                'class' => Langue::class,
                'choice_label' => 'nom',
                'attr' => [
                    'class' => 'js-example-basic-multiple',
                    'placeholder' => 'Langues',
                ],
                'multiple' => true,
                'autocomplete' => true,
                'mapped' => false,
                'required' => false, // Vous pouvez rendre ce champ facultatif
            ])
            ; 
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

