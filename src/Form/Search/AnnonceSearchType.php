<?php

namespace App\Form\Search;

use App\Entity\Moderateur\TypeContrat;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AnnonceSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('query', TextType::class, [
                'required' => false,
                'label' => 'Rechercher',
                'attr' => [
                    'placeholder' => 'Entrez votre recherche...',
                ]
            ])
            ->add('typeContrat', EntityType::class, [
                'class' => TypeContrat::class,
                'choice_label' => 'nom',
                'label' => false,
                'required' => false,
                'placeholder' => 'Contrat',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}