<?php

namespace App\Controller\Admin;

use App\Entity\Server;
use App\Entity\User;
use App\Form\Admin\ConnectedDeviceFormType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;

class ServerCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityRepository $entityRepository
    ) {
    }

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
            BooleanField::new('pairing'),
            CollectionField::new('connectedDevices')
                ->setFormTypeOption('entry_type', ConnectedDeviceFormType::class)
                ->allowAdd(false),
            BooleanField::new('active')->setValue(true),
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

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $user = $this->getUser();
        $entityInstance->setUser($user);

        parent::persistEntity($entityManager, $entityInstance);
    }
}
