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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class OrderForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('orderNumber', TextType::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Numéro de commande.',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Order::getStatuses(),
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Statut de la commande.',
            ])
            ->add('totalAmount', TextType::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Montant.',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Description de la commande.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'full-ckeditor-textarea'
                ]
            ])
            ->add('createdAt', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'date-picker'],
                'label' => 'Date de transaction (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Date et heure de la réservation.',
            ])
            // ->add('paymentId')
            // ->add('payerId')
            ->add('currency', EntityType::class, [
                'class' => Devise::class,
                'choice_label' => 'symbole',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Devise choisie pour la commande.',
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
                'choice_label' => 'name',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Devise choisie pour la commande.',
            ])
            ->add('package', EntityType::class, [
                'class' => Package::class,
                'choice_label' => 'name',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Devise choisie pour la commande.',
            ])
            // ->add('transaction', EntityType::class, [
            //     'class' => Transaction::class,
            //     'choice_label' => 'id',
            // ])
            // ->add('invoice', EntityType::class, [
            //     'class' => Invoice::class,
            //     'choice_label' => 'id',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
