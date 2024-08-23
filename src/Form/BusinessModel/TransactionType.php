<?php

namespace App\Form\BusinessModel;

use App\Entity\BusinessModel\Package;
use App\Entity\BusinessModel\Transaction;
use App\Entity\BusinessModel\TypeTransaction;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('amount')
            // ->add('creditsAdded')
            // ->add('details')
            ->add('package', EntityType::class, [
                'class' => Package::class,
                'choice_label' => 'name',
            ])
            ->add('typeTransaction', EntityType::class, [
                'class' => TypeTransaction::class,
                'choice_label' => 'name',
                'expanded' => true,  
                'required' => true, 
                'label' => false
            ])
            ->add('reference')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
