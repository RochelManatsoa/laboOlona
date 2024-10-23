<?php

namespace App\Form\Entreprise;

use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\BoostFacebook;
use App\Entity\Entreprise\JobListing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;

class AnnonceBoostType extends AbstractType
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $sluggerInterface,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('boost', EntityType::class, [
                'class' => Boost::class,
                'choices' => $this->entityManager->getRepository(Boost::class)->findBy(['type' => 'JOB_LISTING']),
                'choice_label' => function ($boost) {
                    return $boost->getName(); 
                },
                'expanded' => true,  
                'required' => false, 
                'placeholder' => 'Pas de boost',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobListing::class,
            'csrf_protection' => false,
        ]);
    }
}
