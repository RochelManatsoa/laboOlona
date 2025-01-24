<?php

namespace App\Form\Facebook;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class RegisterContestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'label' => 'app_register.first_name',
                'required' => true,
                'label_attr' => [
                    'class' => 'fw-bold lead' 
                ],
                'help' => 'Champs obligatoire',
            ])
            ->add('prenom', null, [
                'label' => 'app_register.last_name',
                'required' => true,
                'label_attr' => [
                    'class' => 'fw-bold lead' 
                ],
                'help' => 'Champs obligatoire',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse éléctronique *',
                'required' => true,
                'label_attr' => [
                    'class' => 'fw-bold lead' 
                ],
                'help' => 'Champs obligatoire',
            ])
            ->add('telephone', null, [
                'label' => 'Téléphone *',
                'required' => true,
                'label_attr' => [
                    'class' => 'fw-bold lead' 
                ],
                'help' => 'Champs obligatoire',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
        ]);
    }
}
