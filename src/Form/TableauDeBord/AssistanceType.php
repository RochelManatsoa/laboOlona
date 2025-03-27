<?php

namespace App\Form\TableauDeBord;

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

class AssistanceType extends AbstractType
{

    public function __construct(
        private UserService $userService,
    ){}
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'placeholder' => 'Objet de votre demande'
                ],
                'help' => 'Veuillez préciser l\'objet de votre demande en quelques mots.',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre adresse email *',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'attr' => [
                    'value' => $this->userService->getCurrentUser() ? $this->userService->getCurrentUser()->getEmail() : "",
                ],
                'help' => 'Nous utiliserons cette adresse pour vous recontacter.',
            ])
            ->add('message', TextareaType::class, [
                'required' => false,
                'label' => 'Message *',
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
                ],
                'help' => 'Décrivez votre problème ou posez votre question ici...',
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
