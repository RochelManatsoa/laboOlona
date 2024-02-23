<?php

namespace App\Form\Finance;

use App\Entity\Finance\Employe;
use Symfony\Component\Form\AbstractType;
use App\Form\RegisterFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', RegisterFormType::class, [
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employe::class,
        ]);
    }
}
