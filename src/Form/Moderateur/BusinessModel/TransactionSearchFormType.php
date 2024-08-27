<?php

namespace App\Form\Moderateur\BusinessModel;

use Symfony\Component\Form\AbstractType;
use App\Entity\BusinessModel\Transaction;
use App\Data\BusinessModel\TransactionData;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TransactionSearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Référence'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Transaction::getStatuses(),
                'label' => false,
                'required' => false,
                'placeholder' => 'Status',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransactionData::class,
            'method' => 'GET',
            'csrf_protection' => false
        ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}
