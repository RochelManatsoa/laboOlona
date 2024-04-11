<?php

namespace App\Controller\Admin\Crud;

use App\Entity\CandidateProfile;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;

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
            IdField::new('id')->hideOnForm(),
            TextField::new('titre'),
            TextField::new('candidat.fullName', 'Nom et prénom')->hideOnForm(),
            TextField::new('candidat.nom', 'Nom')->hideOnIndex(),
            TextField::new('candidat.prenom', 'Prénom(s)')->hideOnIndex(),
            DateField::new('candidat.dateInscription', 'Inscrit le')->hideOnForm(),
            DateField::new('candidat.lastLogin', 'Last login')->hideOnForm(),
            TextEditorField::new('resume', 'Biographie')->hideOnIndex(),
            AssociationField::new('competences', 'Compétences')->hideOnIndex(),
            // AssociationField::new('langages', 'Langues')->hideOnIndex(),
        ];
    }
}
