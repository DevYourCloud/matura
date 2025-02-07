<?php

namespace App\Controller\Admin;

use App\Entity\AccessToken;
use App\Model\TokenValidityPeriod;
use App\Service\AccessTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Webmozart\Assert\Assert;

class AccessTokenCrudController extends AbstractCrudController
{
    public function __construct(private AccessTokenManager $accessTokenManager)
    {
    }

    public static function getEntityFqcn(): string
    {
        return AccessToken::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setRequired(true),
            TextField::new('accessToken')->hideOnForm()->hideOnIndex(),
            TextField::new('accessToken')
                ->setLabel('url')
                ->formatValue(function ($value, $entity) {
                    return $this->accessTokenManager->getTokenUrl($entity);
                })
                ->hideOnForm(),
            AssociationField::new('server')->setRequired(true),
            ChoiceField::new('validityPeriod')->setChoices([
                'app.admin.access_token.validity.7_days' => TokenValidityPeriod::SEVEN_DAYS->value,
                'app.admin.access_token.validity.30_days' => TokenValidityPeriod::THIRTY_DAYS->value,
                'app.admin.access_token.validity.3_months' => TokenValidityPeriod::NINETY_DAYS->value,
                'app.admin.access_token.validity.6_months' => TokenValidityPeriod::SIX_MONTHS->value,
                'app.admin.access_token.validity.1_year' => TokenValidityPeriod::ONE_YEAR->value,
            ])
                ->onlyWhenCreating(),
            DateTimeField::new('validity')->hideOnForm(),
            BooleanField::new('active'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    /**
     * @param AccessToken $entityInstance
     *
     * @throws \DateMalformedIntervalStringException
     */
    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        Assert::isInstanceOf($entityInstance, AccessToken::class);

        $this->accessTokenManager->generateAccessTokenData($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }
}
