<?php

namespace App\Controller\Admin\Crud;

use App\Entity\EntrepriseProfile;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class EntrepriseProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EntrepriseProfile::class;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC'])
            ->setEntityLabelInSingular('Entreprise')
            ->setEntityLabelInPlural('Entreprises')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('nom'),
            TextField::new('entreprise.fullName', 'Nom et prénom')->hideOnForm(),
            TextField::new('taille'),
            TextField::new('localisation'),
            TextField::new('siteWeb'),
            TextField::new('status')->hideOnIndex(),
            TextEditorField::new('description')->hideOnIndex(),
        ];
    }
}
