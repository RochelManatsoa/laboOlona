<?php

namespace App\Form\Coworking;

use App\Entity\BusinessModel\Invoice;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Package;
use App\Entity\BusinessModel\Transaction;
use App\Entity\BusinessModel\TypeTransaction;
use App\Entity\Finance\Devise;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status')
            ->add('totalAmount')
            ->add('description')
            ->add('paymentId')
            ->add('currency', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'id',
            ])
            ->add('customer', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            ->add('paymentMethod', EntityType::class, [
                'class' => TypeTransaction::class,
                'choice_label' => 'id',
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
