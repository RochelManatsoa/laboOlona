<?php

namespace App\Form\Moderateur;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class EditedCvType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['form_id']) {
            $builder->setAttribute('id', $options['form_id']);
        }
        $builder
            ->add('cvEdit', FileType::class, [
                'label' => false,
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'custom-file-input'],
                'constraints' => [
                    new File([
                        'maxSize' => '4096k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un document PDF valide.',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'form_id' => null,
        ]);
    }
}
