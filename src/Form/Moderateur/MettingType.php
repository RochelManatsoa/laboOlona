<?php


namespace App\Form\Moderateur;

use App\Entity\Moderateur\Metting;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UuidType;

class MettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => false 
            ])
            ->add('customId', UuidType::class, [
                'label' => 'ID de la conférence',
                'required' => false ,
                'attr' => [
                    'readonly' => true,
                ],
            ])
            ->add('entreprise', EntityType::class, [
                'class' => EntrepriseProfile::class,
                'label' => 'Entreprise',
                'required' => false // L'entreprise peut être nulle
            ])
            ->add('candidat', EntityType::class, [
                'class' => CandidateProfile::class,
                'label' => 'Candidat',
                'required' => false // Le candidat peut être nul
            ])
            ->add('dateRendezVous', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date du Rendez-vous',
                'attr' => [
                    'class' => 'form-control datetime-picker'
                ]
            ])
            ->add('link', TextType::class, [
                'label' => 'Lieu',
                'required' => false // Le lieu peut être nul
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'required' => false // Le lieu peut être nul
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Metting::getStatuses(),
                'label' => 'Statut',
                'required' => false // Le statut peut être nul
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'attr' => [
                    'rows' => 6,
                ],
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Metting::class,
        ]);
    }
}
