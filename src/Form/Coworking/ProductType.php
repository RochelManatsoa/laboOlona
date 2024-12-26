<?php

namespace App\Form\Coworking;

use App\Entity\Coworking\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de du produit',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Boissons' => 'boissons',
                    'Place' => 'place',
                ],
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('price', null, [
                'label' => 'Prix',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],  
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Descriptif du produit',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('imageFile', VichImageType::class, [
                'required' => false,
                'allow_delete' => true, 
                'download_uri' => true, 
                'image_uri' => true,  
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],  
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
