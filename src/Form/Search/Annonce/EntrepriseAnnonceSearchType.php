<?php

namespace App\Form\Search\Annonce;

use App\Entity\Entreprise\JobListing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EntrepriseAnnonceSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Titre de l\'annonce ...',
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => JobListing::getCompanyStatuses(),
                'required' => false,
                'label' => false,
                'placeholder' => 'Status ...',
            ])
            ->add('typeContrat', ChoiceType::class, [
                'choices' => $options['types_contrat'],
                'choice_label' => function($typeContrat) {
                    return $typeContrat->getNom(); // Assurez-vous que getNom() est une méthode valide dans votre entité TypeContrat
                },
                'label' => false,
                'required' => false,
                'placeholder' => 'Contrat',
            ])
            ->add('salaire', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Salaire ...',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'types_contrat' => [],
        ]);
    }
}