<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse éléctronique',
                // 'label_attr' => ['class' => 'text-light'],
            ])
            ->add('telephone', TextType::class, [
                // 'label_attr' => ['class' => 'text-light'],
                'label' => 'Téléphone'
            ])
            ->add('adress', TextType::class, [
                // 'label_attr' => ['class' => 'text-light'],
                'label' => 'Adresse'
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
