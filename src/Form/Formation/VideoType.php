<?php

namespace App\Form\Formation;

use App\Entity\Formation\Video;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VideoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('description')
            ->add('url')
            // ->add('duree')
            // ->add('publieeLe')
            // ->add('miniature')
            // ->add('nombreVues')
            // ->add('nombreLikes')
            // ->add('auteur')
            // ->add('status')
            // ->add('langue')
            // ->add('quality')
            // ->add('metadata')
            ->add('playlist')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Video::class,
        ]);
    }
}
