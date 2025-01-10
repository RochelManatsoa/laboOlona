<?php

namespace App\Form\Coworking;

use App\Entity\Coworking\Contract;
use Symfony\Component\Form\AbstractType;
use App\Form\Autocomplete\UserAutocompleteField;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;

class ContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typePerson', ChoiceType::class, [
                'choices' => [
                    'Société' => true,
                    'Particulier' => false,
                ],
                'label' => 'Personne morale',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Votre entreprise est une personne morale ou une société.',
            ])
            ->add('socialReason', TextType::class, [
                'label' => 'Raison sociale',
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Raison sociale de l\'entreprise.',
            ])
            ->add('siret', TextType::class, [
                'label' => 'Numéro de SIRET',
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Identifiant de l\'entreprise.',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Votre nom.',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Prénom(s)',
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Votre prénom.',
            ])
            ->add('adress', TextType::class, [
                'label' => 'Adresse',
                'constraints' => new Sequentially([
                    new NotBlank(message:'L\'adresse est obligatoire.'),
                    new Length(
                        min: 9,
                        max: 100,
                        minMessage: 'L\'adresse est trop court',
                        maxMessage: 'L\'adresse ne doit pas depasser 100 characters',
                    ),
                ]),
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
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le téléphone est obligatoire.'),
                    new Length(
                        min: 9,
                        max: 13,
                        minMessage: 'Le téléphone est trop court',
                        maxMessage: 'Le téléphone ne doit pas depasser 13 characters',
                    ),
                ]),
                'label' => 'Téléphone',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Numéro de téléphone.',
            ])
            ->add('email', TextType::class, [
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le mail est obligatoire.'),
                ]),
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
                ->add('user', UserAutocompleteField::class, [])
            ;
        }else{
            $builder
            ->add('acceptTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter nos conditions.',
                    ]),
                ],
                'label' => false,
                'attr' => ['data-step' => 2],
            ])
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
