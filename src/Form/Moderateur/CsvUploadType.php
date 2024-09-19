<?php

namespace App\Form\Moderateur;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class CsvUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('csv_file', FileType::class, [
                'label' => false,
                'label_attr' => ['class' => 'col-sm-4 text-center col-form-label'],
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'custom-file-input'],
                'constraints' => [
                    new File([
                        'maxSize' => '4096k',
                        'mimeTypes' => [
                            'text/csv',
                            'text/plain',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un document CSV valide.',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
