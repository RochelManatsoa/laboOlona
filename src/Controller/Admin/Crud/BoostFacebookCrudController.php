<?php

namespace App\Controller\Admin\Crud;

use App\Entity\BusinessModel\BoostFacebook;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class BoostFacebookCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BoostFacebook::class;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC'])
            ->setEntityLabelInSingular('Boost Facebook')
            ->setEntityLabelInPlural('Boosts Facebook')
        ;
    }
}
