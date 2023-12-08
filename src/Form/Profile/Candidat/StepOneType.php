<?php

namespace App\Form\Profile\Candidat;

use App\Form\InfoUserType;
use App\Entity\CandidateProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;

class StepOneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('candidat', InfoUserType::class, ['label' => false])
            ->add('file', FileType::class, [
                'required' => false,
                'label' => 'app_identity_expert_step_one.avatar_desc',
                'attr' => ['class' => 'd-none'],
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'image/bmp',
                        ],
                    ])
                ],
            ])
            ->add('localisation', CountryType::class, [
                'required' => true,
                'label' => 'Pays *',
                'attr' => ['class' => 'd-none'],
            ])
            ->add('birthday', DateType::class, [
                'label' => 'Votre anniversaire',
                'required' => false,
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
                'years' => range(1970, 2010),
                'attr' => ['class' => 'rounded-pill'] 
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CandidateProfile::class,
        ]);
    }
}
