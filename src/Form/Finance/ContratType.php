<?php

namespace App\Form\Finance;

use App\Entity\Finance\Contrat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ContratType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => Contrat::getTypeContrat()
            ])
            ->add('dateDebut', DateType::class,  [
                'label' => 'Début du contrat',
                'years' => range((new \DateTime('now'))->format("Y"), (new \DateTime('now'))->modify('+5 years')->format("Y")),
                'attr' => [] 
            ])
            ->add('dateFin', DateType::class,  [
                'label' => 'Fin du contrat',
                'years' => range((new \DateTime('now'))->format("Y"), (new \DateTime('now'))->modify('+10 years')->format("Y")),
                'attr' => [] 
            ])
            ->add('salaireBase')
            ->add('status', ChoiceType::class, [
                'choices' => Contrat::getStatuses()
            ])
            ->add('commentaire')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contrat::class,
        ]);
    }
}
