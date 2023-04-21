<?php

namespace App\DataFixtures;

use App\Entity\Application;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ApplicationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Base Server
        $nextcloud = new Application();
        $nextcloud
            ->setAlias('n')
            ->setName('nextcloud')
            ->setPort(80)
        ;

        $this->addReference(self::getReferenceKey('nextcloud'), $nextcloud);

        // UnauthorizedServer
        $unAuthorizedApp = new Application();
        $unAuthorizedApp
            ->setAlias('u')
            ->setName('unauthorized')
            ->setPort(80)
        ;
        $this->addReference(self::getReferenceKey('unauthorized'), $unAuthorizedApp);

        // AuthorizedServer
        $authorizedApp = new Application();
        $authorizedApp
            ->setAlias('a')
            ->setName('authorized')
            ->setPort(80)
        ;
        $this->addReference(self::getReferenceKey('authorized'), $authorizedApp);

        // Symfony Request Server
        $symfonyRequestApp = new Application();
        $symfonyRequestApp
            ->setAlias('s')
            ->setName('symfony')
            ->setPort(80)
        ;
        $this->addReference(self::getReferenceKey('symfony-request'), $symfonyRequestApp);

        // Symfony Request Server
        $pairing = new Application();
        $pairing
            ->setAlias('a')
            ->setName('app')
            ->setPort(80)
        ;
        $this->addReference(self::getReferenceKey('pairing-request'), $pairing);
    }

    public static function getReferenceKey(string $name): string
    {
        return 'fixture_application_'.$name;
    }
}
