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
                'label' => 'Adresse éléctronique (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Veuillez entrer une adresse e-mail valide. Exemple : contact@exemple.com.',
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Entrez un numéro de téléphone valide avec l\'indicatif international si nécessaire.',
            ])
            ->add('adress', TextType::class, [
                'label' => 'Adresse (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Indiquez l’adresse postale complète de votre entreprise.',
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
