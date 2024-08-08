<?php

namespace App\Form\Moderateur\Profile;

use App\Data\Profile\CandidatSearchData;
use App\Data\Profile\StatSearchData;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class StatSearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('start', DateType::class, [
                'required' => false,
                'widget' => 'single_text',  
                'format' => 'yyyy-MM-dd',   
                'label' => 'À partir du',
            ])
            ->add('end', DateType::class, [
                'required' => false,
                'widget' => 'single_text',  
                'format' => 'yyyy-MM-dd',   
                'label' => 'À partir du',
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'required' => false,
                'label' => 'Par',
                'placeholder' => 'Modérateur',
                'query_builder' => function (UserRepository $er) {
                    return $er->createQueryBuilder('u')
                              ->where('u.type = :type')
                              ->setParameter('type', 'MODERATEUR');
                },
            ])
            ->add('from', ChoiceType::class, [
                'choices' => [
                    'Aujourd\'hui' => 1,                    
                    'Hier' => 2,                    
                    'Avant-hier' => 3,                    
                    '7 jours' => 7,                    
                    '30 jours' => 30,                    
                ],
                'label' => false,
                'required' => false,
                'placeholder' => 'Depuis',
                'attr' => [
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StatSearchData::class,
            'method' => 'GET',
            'csrf_protection' => false
        ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}
