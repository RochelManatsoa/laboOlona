<?php

namespace App\Form\Profile\Candidat\Edit;

use App\Entity\CandidateProfile;
use Symfony\Component\Form\AbstractType;
use App\Form\Profile\Candidat\Edit\InfoUserType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class StepOneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('candidat', InfoUserType::class, ['label' => false])
            ->add('file', FileType::class, [
                'required' => false,
                'label' => 'app_identity_expert_step_one.avatar',
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
                'label' => 'Pays',
                'attr' => ['class' => 'd-none'],
            ])
            ->add('birthday', DateType::class, [
                'label' => 'Votre anniversaire',
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
                'years' => range(1970, 2010),
                'attr' => ['class' => 'rounded-pill'] 
            ])
            ->add('resume', TextType::class, [
                'label' => 'app_identity_expert.aspiration',
                'required' => false,
                'attr' => [
                    'rows' => 8
                ]
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
