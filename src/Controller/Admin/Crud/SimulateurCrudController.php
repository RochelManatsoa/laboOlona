<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Finance\Simulateur;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class SimulateurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Simulateur::class;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC'])
            ->setEntityLabelInSingular('Simulation')
            ->setEntityLabelInPlural('Simulations')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('employe.user.email', 'email'),
            TextField::new('employe.user.fullName', 'Nom et prénom')->hideOnForm(),
            TextField::new('status'),
            TextField::new('type'),
            IntegerField::new('salaireNet')->hideOnIndex(),
            // AssociationField::new('contrat')->hideOnIndex(),
            TextField::new('deviseSymbole')->hideOnIndex(),
            IntegerField::new('jourRepas')->hideOnIndex(),
            IntegerField::new('prixRepas')->hideOnIndex(),
            IntegerField::new('jourDeplacement')->hideOnIndex(),
            IntegerField::new('prixDeplacement')->hideOnIndex(),
            IntegerField::new('primeNet')->hideOnIndex(),
            DateTimeField::new('createdAt'),
        ];
    }
}
