<?php

namespace App\DataFixtures;

use App\Entity\Application;
use App\Entity\ConnectedDevice;
use App\Entity\Host;
use App\Entity\Server;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DeviceDisabledFixture extends Fixture
{
    public const SERVER_REFERENCE = 'disabled.devyour.cloud';
    public const DISABLED_DEVICE_REFERENCE = 'DISABLED_DEVICE';

    public function load(ObjectManager $manager): void
    {
        // User
        $user = new User();
        $user
            ->setEmail('nick@test.fr')
            ->setFullName('Nick')
            ->setRoles(['ROLE_USER', 'ROLE_ADMIN'])
            ->setPassword('test')
            ->setActive(true)
        ;

        // Base app
        $exampleApp = new Application();
        $exampleApp
            ->setAlias('n')
            ->setName('exampleApp')
            ->setPort(80)
            ->setActive(true)
        ;

        // Server
        $host = new Host();
        $host->setDomain('authorized.devyour.cloud');

        $server = new Server();
        $server
            ->setName('Authorized Server')
            ->setPairing(false)
            ->setHost($host)
            ->setActive(true)
            ->addApp($exampleApp)
        ;

        $exampleApp->createHost();

        $user->addServer($server);

        // Connected Device
        $authorizedDevice = new ConnectedDevice();
        $authorizedDevice
            ->setIp('100.111.222.333')
            ->setUserAgent('Firefox')
            ->setHash('HASH_TEST')
            ->setLastAccessed(new \DateTime('yesterday'))
            ->setActive(false)
        ;

        $server->addConnectedDevice($authorizedDevice);

        $this->addReference(self::SERVER_REFERENCE, $server);

        $this->addReference(self::DISABLED_DEVICE_REFERENCE, $authorizedDevice);

        $manager->persist($user);
        $manager->flush();
    }
}
