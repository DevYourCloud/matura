<?php

namespace App\Controller\Admin;

use App\Entity\Server;
use App\Form\Admin\ConnectedDeviceFormType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Webmozart\Assert\Assert;

class ServerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Server::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            TextField::new('description'),
            AssociationField::new('host')->renderAsEmbeddedForm(),
            BooleanField::new('active')->setValue(true),
            CollectionField::new('apps')
                ->hideWhenCreating()
                ->hideWhenUpdating(),
            BooleanField::new('pairing'),
            CollectionField::new('connectedDevices')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->setFormTypeOption('entry_type', ConnectedDeviceFormType::class)
                ->allowAdd(false),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Servers')
            ->setEntityLabelInSingular('Server')
            ->setPageTitle('index', '%entity_label_plural% listing')

            ->setSearchFields(['name', 'description'])
        ;
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        Assert::isInstanceOf($entityInstance, Server::class);

        $user = $this->getUser();
        $entityInstance->setUser($user);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }
}
