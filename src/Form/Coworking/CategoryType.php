<?php

namespace App\Form\Coworking;

use App\Entity\Coworking\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la catégorie',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'label' => 'Descriptif de la catégorie',
                'constraints' => new Sequentially([
                    new NotBlank(message:'La description est obligatoire.'),
                    new Length(
                        min: 3,
                        minMessage: 'La description est trop court',
                    ),
                ]),
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'choices' => Category::CHOICE_STATUS,
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
            'data_class' => Category::class,
        ]);
    }
}
