<?php

namespace App\Form\Blog;

use App\Entity\Blog\Post;
use App\Entity\Blog\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', TextType::class, [
                'label' => 'Nom d\'utilisateur',
                'label_attr' => [
                    'class' => 'fw-bold fs-7 text-uppercase' 
                ],
            ])
            ->add('content', TextareaType::class, [
                'required' => false,
                'label' => 'Votre message',
                'label_attr' => [
                    'class' => 'fw-bold fs-7 text-uppercase' 
                ],
                'attr' => [
                    'rows' => 6,
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Comment::getStatuses(),
                'label' => 'Status',
                'label_attr' => [
                    'class' => 'fw-bold fs-7 text-uppercase' 
                ],
                'help' => 'Status du commentaire.',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'label_attr' => [
                    'class' => 'fw-bold fs-7 text-uppercase' 
                ],
            ])
            ->add('post', EntityType::class, [
                'class' => Post::class,
                'choice_label' => 'title',
                'label' => 'Article',
                'label_attr' => [
                    'class' => 'fw-bold fs-7 text-uppercase' 
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
