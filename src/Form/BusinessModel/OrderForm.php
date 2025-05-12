<?php

namespace App\Form\BusinessModel;

use App\Entity\User;
use App\Entity\Finance\Devise;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Invoice;
use App\Entity\BusinessModel\Package;
use Symfony\Component\Form\AbstractType;
use App\Entity\BusinessModel\Transaction;
use App\Entity\BusinessModel\TypeTransaction;
use App\Form\Autocomplete\UserAutocompleteField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('orderNumber')
            ->add('updatedAt')
            ->add('status')
            ->add('totalAmount')
            ->add('description')
            ->add('paymentId')
            ->add('payerId')
            ->add('token')
            ->add('currency', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'id',
            ])
            ->add('customer', UserAutocompleteField::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'choice_label' => function(?User $user) {
                    return $user ? $user->getPrenom() . ' ' . $user->getNom() : '';
                },
                'placeholder' => 'Choisir un utilisateur', 
                'required' => false,
                'autocomplete' => true
            ])
            ->add('paymentMethod', EntityType::class, [
                'class' => TypeTransaction::class,
                'choice_label' => 'id',
            ])
            ->add('package', EntityType::class, [
                'class' => Package::class,
                'choice_label' => 'id',
            ])
            ->add('transaction', EntityType::class, [
                'class' => Transaction::class,
                'choice_label' => 'id',
            ])
            ->add('invoice', EntityType::class, [
                'class' => Invoice::class,
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
