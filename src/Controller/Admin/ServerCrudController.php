<?php

namespace App\Controller\Admin;

use App\Entity\Server;
use App\Form\Admin\ConnectedDeviceFormType;
use App\Repository\ConnectedDeviceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ServerCrudController extends AbstractCrudController
{
    public function __construct(private ConnectedDeviceRepositoryInterface $connectedDeviceRepository)
    {
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
            BooleanField::new('active'),
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
        if (!$entityInstance instanceof Server) {
            throw new \LogicException('Invalid type provided');
        }

        $user = $this->getUser();
        $entityInstance->setUser($user);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Server) {
            throw new \LogicException('Invalid type provided');
        }

        parent::updateEntity($entityManager, $entityInstance);

        foreach ($entityInstance->getApps() as $app) {
            $app->createHost();
        }

        if (!$entityInstance->isPairing()) {
            $this->connectedDeviceRepository->removeNonPairedConnectedDevice($entityInstance);
        }

        $entityManager->flush();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }
}
