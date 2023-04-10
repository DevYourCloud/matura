<?php

namespace App\DataFixtures;

use App\Entity\Server;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ServerFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user = $this->getReference(UserFixtures::getReferenceKey('nick'));

        $server = new Server();
        $server
            ->setName("Nick's Server")
            ->createHost('nick.devyour.cloud')
            ->setActive(true)
            ->setUser($user)
            ->addApp($this->getReference(ApplicationFixtures::getReferenceKey('nextcloud')))
        ;
        $this->addReference(self::getReferenceKey('nick'), $server);

        $unauthorizedServer = new Server();
        $unauthorizedServer
            ->setName('Unauthorized Server')
            ->createHost('unauthorized.devyour.cloud')
            ->setActive(true)
            ->setUser($user)
            ->addApp($this->getReference(ApplicationFixtures::getReferenceKey('unauthorized')))
        ;
        $this->addReference(self::getReferenceKey('unauthorized'), $unauthorizedServer);

        $authorizedServer = new Server();
        $authorizedServer
            ->setName('Authorized Server')
            ->createHost('authorized.devyour.cloud')
            ->setActive(true)
            ->setUser($user)
            ->addApp($this->getReference(ApplicationFixtures::getReferenceKey('authorized')))
            ->addConnectedDevices($this->getReference(ConnectedDeviceFixtures::getReferenceKey('authorized')))
        ;

        $this->addReference(self::getReferenceKey('authorized'), $authorizedServer);

        $symfonyRequestServer = new Server();
        $symfonyRequestServer
            ->setName('Symfony Request Server')
            ->createHost('symfony-request.devyour.cloud')
            ->setActive(true)
            ->setUser($user)
            ->addApp($this->getReference(ApplicationFixtures::getReferenceKey('symfony-request')))
            ->addConnectedDevices($this->getReference(ConnectedDeviceFixtures::getReferenceKey('symfony-request')))
        ;

        $this->addReference(self::getReferenceKey('symfony-request'), $symfonyRequestServer);

        $pairingServer = new Server();
        $pairingServer
            ->setName('Pairing Server')
            ->createHost('pairing-request.devyour.cloud')
            ->setActive(true)
            ->setPairing(true)
            ->setUser($user)
            ->addApp($this->getReference(ApplicationFixtures::getReferenceKey('pairing-request')))
        ;

        $this->addReference(self::getReferenceKey('pairing-request'), $pairingServer);
    }

    public static function getReferenceKey(string $name): string
    {
        return 'fixtures_server_'.$name;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ApplicationFixtures::class,
            ConnectedDeviceFixtures::class,
        ];
    }
}
