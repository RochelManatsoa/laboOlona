<?php

namespace App\Form;

use App\Entity\AffiliateTool;
use App\Entity\AffiliateTool\Tag;
use App\Entity\AffiliateTool\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class AffiliateToolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Nom de l\'outil.',
            ])
            ->add('lienAffiliation', TextType::class, [
                'label' => 'Lien d\'affiliation',
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Lien d\'affiliation.',
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Brouillon' => 'draft' ,
                    'En attente de relecture' => 'pending' ,
                    'Publiée' => 'publish' ,
                ],
                'label' => 'Status',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Status de l\'outils.',
            ])
            ->add('image', TextType::class, [
                'label' => 'Lien de l\'image',
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Lien de l\'image.',
            ])
            ->add('shortDescriptionFr', TextareaType::class, [
                'required' => false,
                'label' => 'Description courte ',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Description courte de l\'outil.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'full-ckeditor-textarea'
                ]
            ])
            ->add('descriptionFr', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Description de l\'outil.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'full-ckeditor-textarea'
                ]
            ])
            ->add('sloganFr', TextareaType::class, [
                'required' => false,
                'label' => 'Slogan',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Slogan de l\'outil.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'full-ckeditor-textarea'
                ]
            ])
            ->add('prix')
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
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'nom',
                'autocomplete' => true,
                'label' => 'Catégories',
                'multiple' => true,
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Catégorie de l\'outil',
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'label' => 'Etiquettes',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Etiquetes de l\'outil',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AffiliateTool::class,
        ]);
    }
}
