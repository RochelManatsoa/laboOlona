<?php

namespace App\Form\Profile\Candidat;

use App\Entity\Langue;
use App\Entity\Candidate\Langages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LangagesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('langue', EntityType::class, [
                'class' => Langue::class,
                'label' => 'Langue',
            ])
            ->add('niveau', ChoiceType::class, [
                'label' => 'Niveau',
                'choices'  => [
                    'app_identity_expert_step_two.skill.one' => 1,
                    'app_identity_expert_step_two.skill.two' => 2,
                    'app_identity_expert_step_two.skill.three' => 3,
                    'app_identity_expert_step_two.skill.four' => 4,
                    'app_identity_expert_step_two.skill.five' => 5,
                ],
                'expanded' => false,
                'multiple' => false,
                'required' => true,
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
