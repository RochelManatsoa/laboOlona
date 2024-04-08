<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Errors\ErrorLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class ErrorLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ErrorLog::class;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC'])
            ->setEntityLabelInSingular('Erreur')
            ->setEntityLabelInPlural('Erreurs')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('type'),
            TextareaField::new('message'),
            IntegerField::new('userId'),
            TextareaField::new('fileName')->hideOnIndex(),
            TextField::new('lineNumber')->hideOnIndex(),
            TextareaField::new('userAgent')->hideOnIndex(),
            TextareaField::new('errorMessage')->hideOnIndex(),
            TextareaField::new('errorObject')->hideOnIndex(),
            DateField::new('createdAt'),
        ];
    }
}
