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
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('content', TextareaType::class, [
                'required' => false,
                'label' => 'Contenu',
                'label_attr' => [
                    'class' => 'fw-bold fs-7' 
                ],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Comment::getStatuses(),
                'label' => 'Status',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Status du commentaire.',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ])
            ->add('post', EntityType::class, [
                'class' => Post::class,
                'choice_label' => 'title',
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
