<?php

namespace App\Controller\Admin\Crud;

use App\Entity\CandidateProfile;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CandidateProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CandidateProfile::class;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC'])
            ->setEntityLabelInSingular('Profil')
            ->setEntityLabelInPlural('Profils')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(8),
            IdField::new('id')->hideOnForm(),
            TextField::new('titre'),
            TextField::new('candidat.fullName', 'Nom et prénom')->hideOnForm(),
            TextField::new('candidat.nom', 'Nom')->hideOnIndex(),
            TextField::new('candidat.prenom', 'Prénom(s)')->hideOnIndex(),
            TextEditorField::new('resume', 'Biographie')->hideOnIndex(),
            TextEditorField::new('resultFree', 'Resultat Free')->hideOnIndex(),
            TextEditorField::new('resultPremium', 'Resultat Premium')->hideOnIndex(),
            TextEditorField::new('traductionEn', 'Traduction')->hideOnIndex(),
            TextEditorField::new('tesseractResult', 'Import PDF')->hideOnIndex(),
            FormField::addColumn(4),
            DateField::new('candidat.dateInscription', 'Inscrit le')->hideOnForm(),
            DateField::new('candidat.lastLogin', 'Last login')->hideOnForm(),
            ChoiceField::new('status', 'Statut')->setChoices(CandidateProfile::getStatuses())->hideOnIndex(),
            AssociationField::new('competences', 'Compétences')->hideOnIndex(),
            AssociationField::new('secteurs', 'Secteurs')->hideOnIndex(),
            ImageField::new('fileName', 'Avatar')->setUploadDir('/public/uploads/experts/')->setBasePath('uploads/experts/'),
            AssociationField::new('tarifCandidat', 'Tarif Candidat')->hideOnForm()->setSortable(false),
            TextEditorField::new('resumeCandidat', 'Points forts/Point faible')->hideOnIndex(),
            TextEditorField::new('metaDescription', 'Meta description')->hideOnIndex(),
            TextEditorField::new('badKeywords', 'Mots/Expressions')->hideOnIndex(),
            TextEditorField::new('tools', 'Outils')->hideOnIndex(),
            TextEditorField::new('technologies', 'Technologies')->hideOnIndex(),
        ];
    }
}
