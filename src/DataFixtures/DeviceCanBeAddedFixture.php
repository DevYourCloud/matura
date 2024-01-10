<?php

namespace App\DataFixtures;

use App\Entity\Application;
use App\Entity\Host;
use App\Entity\Server;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DeviceCanBeAddedFixture extends Fixture
{
    public const SERVER_REFERENCE = 'add_device.devyour.cloud';
    public const AUTHORIZED_DEVICE_REFERENCE = 'AUTHORIZED_DEVICE';

    public function load(ObjectManager $manager): void
    {
        // User
        $user = new User();
        $user
            ->setEmail('nick@test.fr')
            ->setFullName('Nick')
            ->setRoles(['ROLE_USER'])
            ->setPassword('test')
            ->setActive(true)
        ;

        // Base app
        $exampleApp = new Application();
        $exampleApp
            ->setAlias('n')
            ->setName('exampleApp')
            ->setPort(80)
        ;

        // Server
        $host = new Host();
        $host->setDomain('authorized.devyour.cloud');

        $server = new Server();
        $server
            ->setName('Allowed device add Server')
            ->setPairing(true)
            ->setHost($host)
            ->setActive(true)
            ->addApp($exampleApp)
        ;

        $exampleApp->createHost();

        $user->addServer($server);

        $this->addReference(self::SERVER_REFERENCE, $server);

        $manager->persist($user);
        $manager->flush();
    }
}
