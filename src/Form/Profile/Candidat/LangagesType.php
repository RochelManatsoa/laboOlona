<?php

namespace App\Form\Profile\Candidat;

use App\Entity\Langue;
use App\Entity\Candidate\Langages;
use App\Repository\LangueRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class LangagesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('langue', EntityType::class, [
                'class' => Langue::class,
                'label' => 'app_identity_expert_step_two.language.label',
            ])
            ->add('niveau', ChoiceType::class, [
                'label' => 'app_identity_expert_step_two.skill.level',
                'choices'  => [
                    'app_identity_expert_step_two.skill.one' => 1,
                    'app_identity_expert_step_two.skill.two' => 2,
                    'app_identity_expert_step_two.skill.three' => 3,
                    'app_identity_expert_step_two.skill.four' => 4,
                    'app_identity_expert_step_two.skill.five' => 5,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'app_identity_expert_step_two.experience.submit',
                'attr' => [
                    'class' => 'btn btn-dark rounded-pill'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Langages::class,
            'langues_non_choisies' => null,
        ]);
    }
}
