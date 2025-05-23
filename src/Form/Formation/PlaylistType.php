<?php

namespace App\Form\Formation;

use App\Entity\Formation\Video;
use App\Entity\Formation\Playlist;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlaylistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('description')
            // ->add('url')
            // ->add('status')
            ->add('videos', EntityType::class, [
                'class' => Video::class,
                'multiple' => true,
                'autocomplete' => true
            ])
            // ->add('miniature')
            // ->add('dureeTotale')
            // ->add('metadata')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Playlist::class,
        ]);
    }
}
