<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Finance\Employe;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class EmployeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Employe::class;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC'])
            ->setEntityLabelInSingular('Employer')
            ->setEntityLabelInPlural('Employers')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('user.email', 'email'),
            TextField::new('user.fullName', 'Nom et prénom')->hideOnForm(),
            TextField::new('user.nom', 'Nom')->hideOnIndex(),
            TextField::new('user.prenom', 'Prénom(s)')->hideOnIndex(),
            IntegerField::new('simulateurCount', 'Simulations')
        ->formatValue(function ($value, $entity) {
            return $entity->getSimulateurCount();
        })
        ->hideOnForm(),
            DateField::new('user.dateInscription', 'Inscrit le')->hideOnForm(),
            DateTimeField::new('user.lastLogin', 'Last login')->hideOnForm(),
            // CollectionField::new('sumulateurs', 'Simulateurs')->hideOnForm(),
        ];
    }
}
