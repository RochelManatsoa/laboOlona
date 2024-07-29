<?php

namespace App\Controller\Admin;

use App\Entity\Entreprise\BudgetAnnonce;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class BudgetAnnonceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BudgetAnnonce::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addPanel('Informations de l\'annonce'),  // Démarre une nouvelle section
            FormField::addColumn(4),  
            TextField::new('annonce.id', 'ID')->hideOnForm(),
            TextField::new('annonce.titre', 'Titre annonce')->hideOnForm(),
            
            FormField::addColumn(4),  
            ChoiceField::new('typeBudget', 'Type')->setChoices(BudgetAnnonce::arrayTarifType()),
    
            FormField::addColumn(4),  
            NumberField::new('montant', 'Montant'),
    
            FormField::addColumn(4),  
            AssociationField::new('currency', 'Devise')
        ];
    }
}
