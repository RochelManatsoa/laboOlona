<?php

namespace App\Form\Coworking;

use App\Entity\Coworking\Contract;
use Symfony\Component\Form\AbstractType;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;

class ContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typePerson', null, [
                'label' => 'Société',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('socialReason', TextType::class, [
                'label' => 'Raison sociale',
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Raison sociale de l\'entreprise.',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Nom',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Votre nom.',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Prénom(s)',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Votre prénom.',
            ])
            ->add('adress', TextType::class, [
                'label' => 'Adresse',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Votre adresse.',
            ])
            ->add('localisation', CountryType::class, [
                'required' => true,
                'label' => 'Pays',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'data' => 'MG',
                'help' => 'Localisation.',
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Numéro de téléphone.',
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Adresse email.',
            ])
        ;
        
        if ($options['is_admin']) {
            $builder
                ->add('status', ChoiceType::class, [
                    'choices' => Contract::getStatuses(),
                    'label' => 'Statut',
                    'label_attr' => [
                        'class' => 'fw-bold fs-5' 
                    ],
                    'help' => 'Status du contrat.',
                ])
            ;
        }else{
            $builder
            ->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'contact',
                'locale' => 'fr',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class,
            'is_admin' => false,
        ]);
    }
}
