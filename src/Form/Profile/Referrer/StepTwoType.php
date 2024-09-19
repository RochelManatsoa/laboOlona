<?php

namespace App\Form\Profile\Referrer;

use App\Entity\ReferrerProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class StepTwoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('raisonSocial', TextType::class, [
                'required' => false,
                'label' => false,
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.']),
            ])
            ->add('nif', TextType::class, [
                'required' => false,
                'label' => false,
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.'])
            ])
            ->add('statutJuridique', TextType::class, [
                'required' => false,
                'label' => false,
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.'])
            ])
            ->add('creation', DateType::class, [
                'label' => false,
                'required' => false,
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
                'years' => range(1970, 2010),
                'attr' => ['class' => 'rounded-pill'] 
            ])
            ->add('adressePro', TextType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('telephonePro', TextType::class, [
                'required' => false,
                'label' => false,
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.'])
            ])
            ->add('emailPro', TextType::class, [
                'required' => false,
                'label' => false,
                'constraints' => new NotBlank(['message' => 'Vous devez remplir ce champ.'])
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReferrerProfile::class,
        ]);
    }
}
