<?php

namespace App\Controller\Admin;

use App\Entity\ConnectedDevice;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityRepository as ORMEntityRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;

class ConnectedDeviceCrudController extends AbstractCrudController
{
    public function __construct(
        protected EncryptionService $encryptionService,
    ) {
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->container->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        // $qb->andWhere('entity.name IS NOT NULL');

        return $qb;
    }

    public static function getEntityFqcn(): string
    {
        return ConnectedDevice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Whitelisted Devices')
            ->setEntityLabelInSingular('Device')
            ->setPageTitle('index', '%entity_label_plural% listing')
            ->setDefaultSort(['active' => 'DESC', 'lastAccessed' => 'DESC', 'createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $user = $this->getUser();

        return [
            IdField::new('id'),

            TextField::new('name'),
            AssociationField::new('server')
                ->setFormTypeOption('query_builder', function (ORMEntityRepository $entityRepository) use ($user) {
                    return $entityRepository->createQueryBuilder('s')
                        ->andWhere('s.user = :user')
                        ->setParameter(':user', $user)
                    ;
                }),
            TextField::new('ip'),
            TextField::new('userAgent'),
            TextField::new('accessCode')->setDisabled()->setRequired(false)->onlyWhenUpdating(),
            TextField::new('accessCode')->onlyOnDetail(),
            DateTimeField::new('accessCodeGeneratedAt')->setDisabled()->setRequired(false)->onlyWhenUpdating(),
            TextField::new('hash')
                ->setDisabled(true)
                ->setRequired(false)
                ->onlyWhenUpdating(),
            DateTimeField::new('lastAccessed')
                ->setDisabled(true)
                ->setRequired(false),
            BooleanField::new('active'),
        ];
    }
}
