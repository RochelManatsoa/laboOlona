<?php

namespace App\Form\Moderateur;

use App\Service\User\UserService;
use App\Entity\Moderateur\ContactForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactFormType extends AbstractType
{

    public function __construct(
        private UserService $userService,
    ){
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'app_home.contact.title',
                'attr' => [
                    'placeholder' => 'app_home.contact.placeholder.title'
                ]
            ])
            ->add('message', TextareaType::class, [
                'label' => 'app_home.contact.message',
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'app_home.contact.email',
                'attr' => [
                    'placeholder' => 'app_home.contact.placeholder.email',
                    'value' => $this->userService->getCurrentUser() ? $this->userService->getCurrentUser()->getEmail() : "",
                ]
            ])
            ->add('numero', TextType::class, [
                'label' => 'app_home.contact.number',
                'required' => false,
                'attr' => [
                    'placeholder' => 'app_home.contact.placeholder.number'
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => false,
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devriez accepter nos conditions.',
                    ]),
                ],
                'attr' => [
                    'label' => 'J\'accepte les termes et conditions.',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactForm::class,
        ]);
    }
}
