<?php

namespace App\Form;

use App\Entity\Prestation;
use App\Entity\BusinessModel\Boost;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use App\Entity\BusinessModel\BoostFacebook;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;

class PrestationBoostType extends AbstractType
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $sluggerInterface,
    ) {}
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('boost', EntityType::class, [
                'class' => Boost::class,
                'attr' => ['class' => 'boost-select', 'data-html' => true],
                'choices' => $this->entityManager->getRepository(Boost::class)->findBy(['type' => $options['boostType']]),
                'choice_label' => function ($boost) {
                    return $boost->getName().' ('.$boost->getCredit().' crédits)'; 
                },
                'choice_attr' => function($boost) {
                    return ['data-content' => $boost->getDescription()];
                },
                'expanded' => true,  
                'required' => false, 
                'placeholder' => 'Pas de boost',
                'label' => false,
                'help' => 'Choisissez un boost pour augmenter la visibilité de votre prestation (optionnel).',
            ])
            ->add('boostFacebook', EntityType::class, [
                'class' => BoostFacebook::class,
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
            'data_class' => Prestation::class,
            'boostType' => "",
            'csrf_protection' => false,
        ]);
    }
}
