<?php

namespace App\WhiteLabel\Form\Client1;

use App\WhiteLabel\Entity\Client1\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('nom')
            ->add('prenom')
            ->add('telephone')
        ;
        if (!$options['isEdit']) {
            $builder->add('password', PasswordType::class);
        }
        $builder->add('roles', ChoiceType::class, [
            'choices' => [
                'Admin' => 'ROLE_ADMIN',
                'Annonceur' => 'ROLE_ANNOUNCER',
                'Candidat' => 'ROLE_CANDIDAT',
                'Recruteur' => 'ROLE_RECRUITER',
            ],
            'autocomplete' => true,
            'multiple' => true,
            'expanded' => false, 
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'isEdit' => false, 
        ]);
    }
}
