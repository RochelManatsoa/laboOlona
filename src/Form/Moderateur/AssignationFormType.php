<?php

namespace App\Form\Moderateur;

use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use App\Entity\Moderateur\Assignation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class AssignationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('entreprise', EntityType::class, [
                'class' => EntrepriseProfile::class,
                'choice_label' => 'nom',
                'mapped' => false,
                'attr' => [
                    'data-controller' => 'job-listing',
                    'data-action' => 'change->job-listing#onEntrepriseChange'
                ],
            ])
            ->add('jobListing')
            ->add('forfait', MoneyType::class, [])
            ->add('commentaire')
            ->add('assigner', SubmitType::class, ['label' => 'Assigner'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Assignation::class,
        ]);
    }
}
