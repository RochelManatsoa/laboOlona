<?php

namespace App\Form\Coworking;

use App\Entity\Coworking\Place;
use App\Entity\Coworking\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PlaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la place',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Espace',
                'choices' => [
                    'Espace 1' => 'espace_1',
                    'Espace 2' => 'espace_2',
                ],
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('price', TextType::class, [
                'label' => 'Prix',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Descriptif de la place',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'required' => false,
                'allow_delete' => true, 
                'download_uri' => true, 
                'image_uri' => true,    
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Place::class,
        ]);
    }
}
