<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Moderateur\Invitation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class InvitationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Invitation::class;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC'])
            ->setEntityLabelInSingular('Invitation')
            ->setEntityLabelInPlural('Invitations')
        ;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
