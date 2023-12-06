<?php

namespace App\Controller\Admin;

use App\Entity\ConnectedDevice;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository as ORMEntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ConnectedDeviceCrudController extends AbstractCrudController
{
    public function __construct(
        protected EncryptionService $encryptionService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ConnectedDevice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['lastAccessed' => 'DESC', 'createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $user = $this->getUser();

        return [
            IdField::new('id'),
            AssociationField::new('server')
                // ->setQueryBuilder(
                //     fn (QueryBuilder $queryBuilder) => $this->connectedDeviceRepository->findByUserQuery($user)
                // )
                ->setFormTypeOption('query_builder', function (ORMEntityRepository $entityRepository) use ($user) {
                    return $entityRepository->createQueryBuilder('s')
                        ->andWhere('s.user = :user')
                        ->setParameter(':user', $user)
                    ;
                }),
            TextField::new('ip'),
            TextField::new('userAgent'),
            TextField::new('hash')
                ->setDisabled(true)
                ->setRequired(false)
                ->onlyWhenUpdating(),
            DateField::new('lastAccessed')
                ->setDisabled(true)
                ->setRequired(false),
            BooleanField::new('active'),
        ];
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityInstance->setHash($this->hashDeviceData($entityInstance));

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::persistEntity($entityManager, $entityInstance);

        $entityInstance->setHash($this->hashDeviceData($entityInstance));

        parent::updateEntity($entityInstance, $entityInstance);
    }

    private function hashDeviceData(ConnectedDevice $connectedDevice): string
    {
        return $this->encryptionService->createConnectedDeviceHash($connectedDevice);
    }
}
