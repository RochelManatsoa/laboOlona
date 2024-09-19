<?php

namespace App\Form\Moderateur;

use App\Entity\EntrepriseProfile;
use App\Entity\Moderateur\Assignation;
use Symfony\Component\Form\AbstractType;
use App\Form\Moderateur\AssignationFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class AssignationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('status')
            // ->add('commentaire')
            // ->add('dateFinAssignation')
            // ->add('rolePositionVisee')
            // ->add('jobListing', CollectionType::class, [
            //     'entry_type' => AssignationFormType::class,
            //     'entry_options' => ['label' => false],
            //     'label' => false,
            //     'allow_add' => true,
            //     'by_reference' => false,
            // ])
            // ->add('entreprise', EntityType::class, [
            //     'class' => EntrepriseProfile::class,
            //     'choice_label' => 'nom',
            //     'mapped' => false,
            // ])
            ->add('forfait')
            ->add('jobListing', AssignationFormType::class, [
                    'label' => false,
            ])
            // ->add('commentaire')
            // ->add('assigner', SubmitType::class, ['label' => 'Assigner'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Assignation::class,
        ]);
    }
}
