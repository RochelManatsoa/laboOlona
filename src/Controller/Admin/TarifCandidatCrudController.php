<?php

namespace App\Controller\Admin;

use App\Entity\Candidate\TarifCandidat;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class TarifCandidatCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TarifCandidat::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addPanel('Informations du Candidat'),  // Démarre une nouvelle section
            FormField::addColumn(4),  
            TextField::new('candidat.matricule', 'Matricule')->hideOnForm(),
            TextField::new('candidat', 'Nom')->hideOnForm(),
            
            FormField::addColumn(4),  
            ChoiceField::new('typeTarif', 'Type')->setChoices(TarifCandidat::arrayTarifType()),
    
            FormField::addColumn(4),  
            NumberField::new('montant', 'Montant'),
    
            FormField::addColumn(4),  
            AssociationField::new('currency', 'Devise')
        ];
    }
}
