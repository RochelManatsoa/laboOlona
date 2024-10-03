<?php

namespace App\Form\BusinessModel;

use App\Entity\User;
use App\Entity\Finance\Devise;
use App\Entity\BusinessModel\Order;
use Symfony\Component\Form\AbstractType;
use App\Entity\BusinessModel\TypeTransaction;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('paymentMethod', EntityType::class, [
                'class' => TypeTransaction::class,
                'choice_label' => 'name',
                'expanded' => true,  
                'required' => true, 
                'label' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
