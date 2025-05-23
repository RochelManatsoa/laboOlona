<?php

namespace App\Form\Moderateur;

use App\Entity\AffiliateTool;
use App\Entity\AffiliateTool\Category;
use App\Entity\AffiliateTool\Tag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class AffiliateToolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [])
            ->add('descriptionFr', TextareaType::class, [])
            ->add('lienAffiliation', TextType::class, [])
            ->add('commission', MoneyType::class, [])
            ->add('sloganFr', TextType::class, [])
            ->add('type', TextType::class, [])
            ->add('image', TextType::class, [])
            ->add('customId', TextType::class, [])
            ->add('shortDescriptionFr', TextareaType::class, [])
            ->add('prix', MoneyType::class, [])
            ->add('status', TextType::class, [])
            ->add('featured', null, [])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'multiple' => true,
                'expanded' => true
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'multiple' => true,
                'autocomplete' => true
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
