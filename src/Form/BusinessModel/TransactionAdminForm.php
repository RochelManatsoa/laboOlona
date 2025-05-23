<?php

namespace App\Form\BusinessModel;

use App\Entity\User;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Package;
use Symfony\Component\Form\AbstractType;
use App\Entity\BusinessModel\Transaction;
use App\Entity\BusinessModel\TypeTransaction;
use App\Form\Autocomplete\UserAutocompleteField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TransactionAdminForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', TextType::class, [
                'label' => 'Montant',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Montant de la transaction.',
            ])
            ->add('creditsAdded', IntegerType::class, [
                'label' => 'Crédits',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Crédits ajoutés.',
            ])
            ->add('transactionDate', DateTimeType::class, [
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
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Référence de la transaction.',
            ])
            // ->add('status')
            ->add('details', TextareaType::class, [
                'required' => false,
                'label' => 'Contenu',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Contenu de l\'article.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'full-ckeditor-textarea'
                ]
            ])
            // ->add('token')
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Téléphone de l\'utilisateur.',
            ])
            // ->add('updatedAt')
            ->add('package', EntityType::class, [
                'label' => 'Pack',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Pack de l\'utilisateur.',
                'class' => Package::class,
                'choice_label' => 'name',
            ])
            ->add('user', UserAutocompleteField::class, [
                'label' => 'Utilisateur',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Utilisateur.',
                'required' => false,
            ])
            ->add('typeTransaction', EntityType::class, [
                'label' => 'Méthode',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Méthode de transaction.',
                'class' => TypeTransaction::class,
                'choice_label' => 'name',
            ])
            // ->add('command', EntityType::class, [
            //     'class' => Order::class,
            //     'choice_label' => 'id',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
