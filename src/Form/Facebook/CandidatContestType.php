<?php

namespace App\Form\Facebook;

use App\Entity\Secteur;
use App\Entity\CandidateProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Sequentially;

class CandidatContestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'required' => true,
                'label' => 'Titre professionnel *',
                'label_attr' => [
                    'class' => 'fw-bold lead' 
                ],
                'help' => 'Ex: Assistante virtuelle',
            ])
            ->add('secteurs', EntityType::class, [
                'class' => Secteur::class,
                'label' => 'Secteur d\'expertise *',
                'choice_label' => 'nom',
                'autocomplete' => true,
                'multiple' => true,
                'required' => true,
                'label_attr' => [
                    'class' => 'fw-bold lead' 
                ],
                'help' => 'Vos secteurs d\'activité',
            ])
            ->add('cv', FileType::class, [
                'label' => 'app_identity_expert.cv',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'custom-file-input'],
                'constraints' =>  new Sequentially([
                    new NotBlank([
                        'message' => 'Champ obligatoire',
                    ]),
                    new File([
                        'maxSize' => '1024k',
                        'maxSizeMessage' => 'La taille du fichier ne doit pas dépasser 1 Mo.', 
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un document PDF valide',
                    ]),
                ]),
                'label_attr' => [
                    'class' => 'fw-bold lead' 
                ],
                'help' => 'Format PDF | La taille du fichier ne doit pas dépasser 1 Mo.',
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
