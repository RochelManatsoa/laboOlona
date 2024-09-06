<?php

namespace App\Form\V2;

use App\Entity\User;
use App\Form\V2\ReferrerType;
use App\Form\V2\RecruiterType;
use App\Entity\ReferrerProfile;
use App\Entity\EntrepriseProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use App\Form\Profile\Candidat\Edit\StepThreeType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => User::getTypeAccount(),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => false,
            ])
            ->add('email')
            ->add('nom')
            ->add('prenom')
            ->add('telephone')
            ->add('adress')
            ->add('candidateProfile', CandidateType::class, [])
            ->add('entrepriseProfile', RecruiterType::class, [])
            ->add('referrerProfile', ReferrerType::class, [])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
