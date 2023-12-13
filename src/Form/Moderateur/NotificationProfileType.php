<?php

namespace App\Form\Moderateur;

use App\Entity\Notification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class NotificationProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('dateMessage')
            // ->add('isRead')
            ->add('titre')
            ->add('contenu', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'rows' => 8
                ]
            ])
            // ->add('type')
            // ->add('status')
            // ->add('expediteur')
            // ->add('destinataire')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Notification::class,
        ]);
    }
}
