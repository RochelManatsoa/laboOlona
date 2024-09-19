<?php

namespace App\Form\BusinessModel;

use App\Entity\BusinessModel\Package;
use Symfony\Component\Form\AbstractType;
use App\Entity\BusinessModel\Transaction;
use App\Entity\BusinessModel\TypeTransaction;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use App\Form\BusinessModel\TransactionReferenceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
            ->add('amount', NumberType::class, [
                'label' => 'Montant de la transaction (*)',
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence de la transaction (*)',
            ])
            ->add('transactionReferences', CollectionType::class, [
                'entry_type' => TransactionReferenceType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Payer sur plusieurs plateformes',
                'entry_options' => ['label' => false],
                'attr' => [
                    'data-controller' => 'form-collection',
                    'data-form-collection-add-label-value' => 'Ajouter une référence',
                    'data-form-collection-delete-label-value' => 'Supprimer'
                ]
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
