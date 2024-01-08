<?php

namespace App\Controller\Admin;

use App\Entity\Application;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Webmozart\Assert\Assert;

class ApplicationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Application::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            TextField::new('host')
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            TextField::new('alias')
                ->setHelp('app.admin.host_server.example')
                ->hideOnIndex()
                ->hideOnDetail(),
            IntegerField::new('port'),
            AssociationField::new('server'),
            BooleanField::new('active')->setValue(true),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        Assert::isInstanceOf($entityInstance, Application::class);

        $entityInstance->createHost();

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        Assert::isInstanceOf($entityInstance, Application::class);

        $entityInstance->createHost();

        parent::updateEntity($entityManager, $entityInstance);
    }
}
