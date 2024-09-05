<?php

namespace App\Form\BusinessModel;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use App\Entity\BusinessModel\TypeTransaction;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\BusinessModel\TransactionReference;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TransactionReferenceType extends AbstractType
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeTransaction', EntityType::class, [
                'class' => TypeTransaction::class,
                'choices' => $this->entityManager->getRepository(TypeTransaction::class)->findBy([
                    'id' => [1, 2, 3],
                ]),
                'choice_label' => function ($boost) {
                    return $boost->getName(); 
                },
                'label' => 'Plateforme',
            ])
            ->add('reference', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'La référence ne doit pas être vide.',
                    ]),
                ],
                'label' => 'Référence de transaction (*)',
            ])
            ->add('montant', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le montant ne doit pas être vide.',
                    ]),
                ],
                'label' => 'Montant (*)',
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransactionReference::class,
        ]);
    }
}
