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
            ->add('email', null, ['label' => 'Email (obligatoire)'])
            ->add('nom')
            ->add('prenom')
            ->add('telephone', null, ['label' => 'Téléphone (obligatoire)'])
            ->add('adress', null, ['label' => 'Adresse (obligatoire)'])
            ->add('postalCode', null, ['label' => 'Code postal (obligatoire)'])
            ->add('city', null, ['label' => 'Ville (obligatoire)'])
            ->add('candidateProfile', CandidateType::class, [
                'label' => false,
            ])
            ->add('entrepriseProfile', RecruiterType::class, [
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
