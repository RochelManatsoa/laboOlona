<?php

namespace App\WhiteLabel\Form\Client1;

use App\WhiteLabel\Entity\Client1\Finance\Devise;
use App\WhiteLabel\Entity\Client1\Entreprise\PrimeAnnonce;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class PrimeAnnonceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', IntegerType::class,[
                'label' => false,
                'required' => false,
            ])
            ->add('devise', EntityType::class, [
                'label' => false,
                'mapped' => true,
                'class' => Devise::class,
                'attr' => [
                    'data-controller' => 'prime-taux',
                    'data-action' => 'change->prime-taux#onDeviseChange'
                ],
            ])
            ->add('taux', HiddenType::class, [
                'attr' =>  [
                    'data-id' => 'primeAnnonce_taux',
                    'value' => $options['default_devise'] ? $options['default_devise']->getTaux() : null,
                ]
            ])
            ->add('symbole', HiddenType::class, [
                'attr' =>  [
                    'data-id' => 'primeAnnonce_symbole',
                ],
                'data' => '€',
            ])
        ;
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $devise = $form->get('devise')->getData();

            if ($devise) {
                $taux = $form->get('taux')->getData();
                $form->get('taux')->setData($taux);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrimeAnnonce::class,
            'default_devise' => null,
        ]);
    }
}
