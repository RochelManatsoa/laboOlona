<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Notification;
use App\Entity\TemplateEmail;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class TemplateEmailCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TemplateEmail::class;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC'])
            ->setEntityLabelInSingular('Modèle email')
            ->setEntityLabelInPlural('Modèles email')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('categorie'),
            ChoiceField::new('type')->setChoices(Notification::getInverseStatuses()),
            ChoiceField::new('compte')->setChoices(User::getInverseChoices()),
            TextField::new('titre'),
            TextEditorField::new('contenu')->hideOnIndex(),
        ];
    }
}
