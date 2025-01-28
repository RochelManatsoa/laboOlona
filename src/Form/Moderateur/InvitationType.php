<?php

namespace App\Form\Moderateur;

use App\Entity\User;
use App\Entity\Moderateur\Invitation;
use Symfony\Component\Form\AbstractType;
use App\Form\Autocomplete\UserAutocompleteField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class InvitationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reader', UserAutocompleteField::class, [
                'label' => 'Utilisateur',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
            ]) 
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invitation::class,
        ]);
    }
}
