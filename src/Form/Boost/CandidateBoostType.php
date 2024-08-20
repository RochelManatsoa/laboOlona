<?php

namespace App\Form\Boost;

use App\Entity\CandidateProfile;
use App\Entity\BusinessModel\Boost;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CandidateBoostType extends AbstractType
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('boost', EntityType::class, [
                'class' => Boost::class,
                'choices' => $this->entityManager->getRepository(Boost::class)->findBy(['type' => 'PROFILE_CANDIDATE']),
                'choice_label' => function ($boost) {
                    return $boost->getName(); 
                },
                'expanded' => true,  
                'required' => false, 
                'placeholder' => 'Pas de boost',
                'label' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CandidateProfile::class,
            'csrf_protection' => false,
        ]);
    }
}
