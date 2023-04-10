<?php

namespace App\Controller\Admin;

use App\Entity\Host;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class HostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Host::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('domain'),
        ];
    }
}
