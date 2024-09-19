<?php

namespace App\Form\Moderateur;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class EntrepriseContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse éléctronique',
                'label_attr' => ['class' => ''],
            ])
            ->add('telephone', TextType::class, [
                'label_attr' => ['class' => ''],
                'label' => 'Téléphone'
            ])
            ->add('adress', TextType::class, [
                'label_attr' => ['class' => ''],
                'label' => 'Adresse'
            ])
            // ->add('nom', TextType::class, [
            //     'label_attr' => ['class' => ''],
            //     'label' => 'Nom'
            // ])
            // ->add('prenom', TextType::class, [
            //     'label_attr' => ['class' => ''],
            //     'label' => 'Prénom(s)'
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
