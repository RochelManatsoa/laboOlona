<?php

namespace App\Form\Referrer;

use App\Entity\Entreprise\JobListing;
use App\Entity\Referrer\Referral;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReferralType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('referredEmail', TextType::class, [
                'label' => 'Email',
                'required' => false,
                'constraints' => [
                    new Email([
                        'message' => 'Veuillez entrer un email valide.',
                    ]),
                    new NotBlank([
                        'message' => 'Veuillez remplir ce champ.',
                    ]),
                ],
            ])
            ->add('annonce', EntityType::class, [
                'class' => JobListing::class,
                'disabled' => true, 
                'attr' => [
                    'readonly' => true, 
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Votre message (facultatif)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Referral::class,
        ]);
    }
}
