<?php

namespace App\Form\Moderateur;

use App\Entity\CandidateProfile;
use Symfony\Component\Form\AbstractType;
use App\Form\Moderateur\AssignationFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class AssignateProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['form_id']) {
            $builder->setAttribute('id', $options['form_id']);
        }
        $builder
            ->add('assignations', CollectionType::class, [
                'entry_type' => AssignationFormType::class,
                'entry_options' => ['label' => false],
                'label' => false,
                'allow_add' => true,
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CandidateProfile::class,
            'form_id' => null, // Ajoutez cette ligne
        ]);
    }
}
