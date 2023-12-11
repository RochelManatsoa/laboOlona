<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class InfoUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
                'label' => 'Nom *',
            ])
            ->add('prenom', TextType::class, [
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
                'label' => 'Prénom(s) *',
            ])
            ->add('telephone', TextType::class, [
                'label' => 'app_identity_company.phone',
                'required' => true,
            ])
            ->add('adress', TextType::class, [
                'label' => 'Adresse *',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'app_identity_company.email',
                'required' => true,
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
