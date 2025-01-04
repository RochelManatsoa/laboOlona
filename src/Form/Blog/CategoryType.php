<?php

namespace App\Form\Blog;

use App\Entity\Blog\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        
        if ($options['is_admin']) {
            $builder
                ->add('isPublished', CheckboxType::class, [
                    'label' => 'Publiée ?',
                    'label_attr' => [
                        'class' => 'fw-bold fs-5' 
                    ],
                    'help' => 'Status de la catégorie.',
                ])
            ;
        }

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Titre de la catégorie.',
            ])
            ->add('content', TextareaType::class, [
                'required' => false,
                'label' => 'Contenu',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ],
                'help' => 'Description de la catégorie.',
            ])
            ->add('metaTitle', TextType::class, [
                'label' => 'Meta Title',
                'label_attr' => [
                    'class' => 'fw-bold fs-7' 
                ],
                'help' => 'Visible dans les moteurs de recherche.',
            ])
            ->add('metaDescription', TextareaType::class, [
                'required' => false,
                'label' => 'Meta Description',
                'label_attr' => [
                    'class' => 'fw-bold fs-7' 
                ],
                'help' => 'Visible dans les moteurs de recherche.',
            ])
            ->add('metaKeywords', TextType::class, [
                'label' => 'Mots clés',
                'label_attr' => [
                    'class' => 'fw-bold fs-7' 
                ],
                'help' => '3 mots maximum',
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image de mise en avant',
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
            'data_class' => Category::class,
            'is_admin' => false,
        ]);
    }
}
