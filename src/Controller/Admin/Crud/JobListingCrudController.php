<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Entreprise\JobListing;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class JobListingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return JobListing::class;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC'])
            ->setEntityLabelInSingular('Annonce')
            ->setEntityLabelInPlural('Annonces')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(8),
            IdField::new('id')->hideOnForm(),
            TextField::new('titre'),
            ChoiceField::new('status', 'Statut de l\'annonce')->setChoices(JobListing::getStatuses()),
            IntegerField::new('nombrePoste', 'Nombre de poste')->hideOnIndex(),
            AssociationField::new('typeContrat', 'Type de contrat')->hideOnIndex(),
            TextEditorField::new('description', 'Contenu')->hideOnIndex(),
            TextEditorField::new('cleanDescription', 'Contenu Propre')->hideOnIndex(),
            TextEditorField::new('shortDescription', 'OpenAI Description')->hideOnIndex(),
            FormField::addColumn(4),
            BooleanField::new('isGenerated', 'Report IA')->hideOnIndex(),
            BooleanField::new('isPublishedOnFacebook', 'Publiée sur Facebook')->hideOnIndex()->setFormTypeOption('disabled', true),
            BooleanField::new('isNotified', 'Notification envoyée')->hideOnIndex()->setFormTypeOption('disabled', true),
            TextField::new('entreprise.nom', 'Entreprise'),
            TextEditorField::new('entreprise.description', 'A propos de l\'entreprise')->hideOnIndex(),
            DateField::new('dateCreation', 'Créée le'),
            DateField::new('dateExpiration', 'Expire le'),
            DateField::new('updatedAt', 'Dernière modification le'),
            AssociationField::new('competences', 'Compétences')->hideOnIndex(),
            AssociationField::new('secteur', 'Secteur'),
        ];
    }

}
