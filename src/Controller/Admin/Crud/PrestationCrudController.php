<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Prestation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PrestationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Prestation::class;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC'])
            ->setEntityLabelInSingular('Prestation')
            ->setEntityLabelInPlural('Prestations')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(8),
            IdField::new('id')->hideOnForm(),
            TextField::new('titre'),
            TextField::new('status', 'Statut'),
            TextEditorField::new('description', 'Contenu')->hideOnIndex(),
            TextEditorField::new('cleanDescription', 'Contenu Propre')->hideOnIndex(),
            TextEditorField::new('openai', 'OpenAI Description')->hideOnIndex(),
            FormField::addColumn(4),
            BooleanField::new('isGenerated', 'Report IA')->hideOnIndex(),
            // ArrayField::new('specialisations', 'Specialisations')->hideOnIndex(),
            // ArrayField::new('competencesRequises', 'Compétences')->hideOnIndex(),
            DateField::new('createdAt', 'Créée le'),
        ];
    }
    
}
