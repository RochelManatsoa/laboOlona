<?php

namespace App\Form\V2;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\Regex;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => false,
                'label' => 'Adresse email *',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Veuillez entrer une adresse email valide. Elle sera utilisée pour vous contacter.',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Champ obligatoire.'),
                ]),
            ])
            ->add('nom', TextType::class, [
                'required' => false,
                'label' => 'Nom *',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Entrez votre nom de famille.',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Champ obligatoire.'),
                ]),
            ])
            ->add('prenom', TextType::class, [
                'required' => false,
                'label' => 'Prénom(s) *',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Entrez vos prénoms complets.',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Champ obligatoire.'),
                ]),
            ])
            ->add('telephone', TextType::class, [
                'required' => false,
                'label' => 'Téléphone *',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Veuillez entrer un numéro de téléphone valide.',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Champ obligatoire.'),
                    new Regex(pattern: '/^\+?\d{1,4}?[-.\s]?(\(?\d{1,4}?\)?[-.\s]?)?\d{1,4}[-.\s]?\d{1,4}[-.\s]?\d{1,9}$/', message:'Numéro de téléphone invalide.'),
                ]),
            ])
            ->add('adress', TextType::class, [
                'required' => false,
                'label' => 'Adresse *',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Saisissez votre adresse complète (numéro, rue, etc.).',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Champ obligatoire.'),
                ]),
            ])
            ->add('postalCode', TextType::class, [
                'required' => false,
                'label' => 'Code postal *',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Code postal correspondant à votre lieu de résidence.',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Champ obligatoire.'),
                    new Regex(pattern: '/^[a-zA-Z0-9]{3,6}$/', message:'Code postal invalide.'),
                ]),
            ])
            ->add('city', TextType::class, [
                'required' => false,
                'label' => 'Ville *',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Saisissez la ville dans laquelle vous résidez.',
                'constraints' => new Sequentially([
                    new NotBlank(message:'Champ obligatoire.'),
                ]),
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
