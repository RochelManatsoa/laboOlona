<?php

namespace App\Form\Blog;

use App\Entity\User;
use App\Entity\Blog\Post;
use App\Entity\Blog\Category;
use Symfony\Component\Form\AbstractType;
use App\Form\Autocomplete\UserAutocompleteField;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Titre de l\'article.',
            ])
            ->add('content', TextareaType::class, [
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
                'help' => '3 mots maximum.',
            ])
            ->add('category', EntityType::class, [
                'label' => 'Catégorie',
                'class' => Category::class,
                'choice_label' => 'title',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Catégorie de l\'article',
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
        
        if ($options['is_admin']) {
            $builder
                ->add('author', UserAutocompleteField::class, [
                    'label' => 'Auteur',
                    'label_attr' => [
                        'class' => 'fw-bold fs-6' 
                    ],
                    'help' => 'Auteur de l\'article.',
                ])
                ->add('status', ChoiceType::class, [
                    'choices' => Post::getStatuses(),
                    'label' => 'Status',
                    'label_attr' => [
                        'class' => 'fw-bold fs-5' 
                    ],
                    'help' => 'Status de l\'article.',
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'is_admin' => false,
        ]);
    }
}
