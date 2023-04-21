<?php

namespace App\DataFixtures;

use App\Entity\Host;
use App\Entity\Server;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ServerFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user = $this->getReference(UserFixtures::getReferenceKey('nick'));

        $host = new Host();
        $host->setDomain('nick.devyour.cloud');

        $nextcloudApp = $this->getReference(ApplicationFixtures::getReferenceKey('nextcloud'));

        $server = new Server();
        $server
            ->setName("Nick's Server")
            ->setHost($host)
            ->setActive(true)
            ->setUser($user)
            ->addApp($nextcloudApp)
        ;
        $nextcloudApp->createHost();
        $this->addReference(self::getReferenceKey('nick'), $server);

        $host = new Host();
        $host->setDomain('unauthorized.devyour.cloud');

        $unauthorizedApp = $this->getReference(ApplicationFixtures::getReferenceKey('unauthorized'));

        $unauthorizedServer = new Server();
        $unauthorizedServer
            ->setName('Unauthorized Server')
            ->setHost($host)
            ->setActive(true)
            ->setUser($user)
            ->addApp($unauthorizedApp)
        ;

        $unauthorizedApp->createHost();
        $this->addReference(self::getReferenceKey('unauthorized'), $unauthorizedServer);

        $host = new Host();
        $host->setDomain('authorized.devyour.cloud');

        $authorizedApp = $this->getReference(ApplicationFixtures::getReferenceKey('authorized'));

        $authorizedServer = new Server();
        $authorizedServer
            ->setName('Authorized Server')
            ->setHost($host)
            ->setActive(true)
            ->setUser($user)
            ->addApp($authorizedApp)
            ->addConnectedDevices($this->getReference(ConnectedDeviceFixtures::getReferenceKey('authorized')))
        ;

        $authorizedApp->createHost();
        $this->addReference(self::getReferenceKey('authorized'), $authorizedServer);

        $host = new Host();
        $host->setDomain('symfony-request.devyour.cloud');

        $symfonyRequestApp = $this->getReference(ApplicationFixtures::getReferenceKey('symfony-request'));
        $symfonyRequestServer = new Server();
        $symfonyRequestServer
            ->setName('Symfony Request Server')
            ->setHost($host)
            ->setActive(true)
            ->setUser($user)
            ->addApp($symfonyRequestApp)
            ->addConnectedDevices($this->getReference(ConnectedDeviceFixtures::getReferenceKey('symfony-request')))
        ;

        $symfonyRequestApp->createHost();
        $this->addReference(self::getReferenceKey('symfony-request'), $symfonyRequestServer);

        $host = new Host();
        $host->setDomain('pairing-request.devyour.cloud');

        $pairingRequestApp = $this->getReference(ApplicationFixtures::getReferenceKey('pairing-request'));
        $pairingServer = new Server();
        $pairingServer
            ->setName('Pairing Server')
            ->setHost($host)
            ->setActive(true)
            ->setPairing(true)
            ->setUser($user)
            ->addApp($pairingRequestApp)
        ;

        $pairingRequestApp->createHost();

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
