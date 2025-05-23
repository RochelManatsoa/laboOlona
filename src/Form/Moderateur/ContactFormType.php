<?php

namespace App\Form\Moderateur;

use App\Service\User\UserService;
use App\Entity\Moderateur\ContactForm;
use Symfony\Component\Form\AbstractType;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;

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
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'placeholder' => 'app_home.contact.placeholder.title'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'app_home.contact.email',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'placeholder' => 'app_home.contact.placeholder.email',
                    'value' => $this->userService->getCurrentUser() ? $this->userService->getCurrentUser()->getEmail() : "",
                ]
            ])
            ->add('numero', TextType::class, [
                'label' => 'app_home.contact.number',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'required' => false,
                'attr' => [
                    'placeholder' => 'app_home.contact.placeholder.number'
                ]
            ])
            ->add('message', TextareaType::class, [
                'required' => false,
                'label' => 'app_home.contact.message',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'Le message est obligatoire.'),
                    new Length(
                        min: 3,
                        minMessage: 'Le message est trop court',
                    ),
                ]),
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'contact',
                'locale' => 'fr',
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
