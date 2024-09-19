<?php

namespace App\Form\Profile\Candidat;

use App\Entity\Candidate\Social;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SocialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('linkedin', TextType::class, [
                'required' => false,
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
            ])
            ->add('skype', TextType::class, [
                'required' => false,
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
            ])
            ->add('slack', TextType::class, [
                'required' => false,
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
            ])
            ->add('facebook', TextType::class, [
                'required' => false,
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
            ])
            ->add('instagram', TextType::class, [
                'required' => false,
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
            ])
            ->add('github', TextType::class, [
                'required' => false,
                'label' => 'app_identity_expert_step_three.other',
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Social::class,
        ]);
    }
}
