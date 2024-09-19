<?php

namespace App\Form\Profile\Referrer;

use App\Entity\ReferrerProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ReferrerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('raisonSocial', TextType::class, [
                'label' => 'Raison social *',
                'required' => false,
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.']),
            ])
            ->add('nif', TextType::class, [
                'label' => 'NIF *',
                'required' => false,
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.'])
            ])
            ->add('statutJuridique', TextType::class, [
                'required' => false,
                'label' => 'Status juriduque *',
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.'])
            ])
            ->add('creation', DateType::class, [
                'label' => 'Date de création',
                'required' => false,
                'years' => range(1970, 2024),
                'attr' => ['class' => 'rounded-pill'] 
            ])
            ->add('adressePro', TextType::class, [
                'label' => 'Adresse professionnel *',
                'required' => false,
            ])
            ->add('telephonePro', TextType::class, [
                'label' => 'Téléphone professionnel *',
                'required' => false,
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.'])
            ])
            ->add('emailPro', TextType::class, [
                'label' => 'Adresse email professionnel *',
                'required' => false,
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.'])
            ])
            // ->add('description')
            // ->add('totalRewards')
            // ->add('pendingRewards')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReferrerProfile::class,
        ]);
    }
}
