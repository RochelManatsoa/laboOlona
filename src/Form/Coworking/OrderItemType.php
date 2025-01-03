<?php

namespace App\Form\Coworking;

use App\Entity\Coworking\Product;
use App\Entity\BusinessModel\Order;
use App\Entity\Coworking\OrderItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class OrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'label' => 'Article',
                'placeholder' => 'Sélectionnez un article',
                'attr' => [
                    'data-controller' => 'product-order',
                    'data-action' => 'change->product-order#onProductChange'
                ],
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('price', HiddenType::class, [])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}
