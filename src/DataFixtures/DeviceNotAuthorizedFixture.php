<?php

namespace App\DataFixtures;

use App\Entity\Application;
use App\Entity\Host;
use App\Entity\Server;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DeviceNotAuthorizedFixture extends Fixture
{
    public const SERVER_REFERENCE = 'unauthorized.devyour.cloud';

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
        $host->setDomain('unauthorized.devyour.cloud');

        $server = new Server();
        $server
            ->setName('Unauthorized Server')
            ->setHost($host)
            ->setActive(true)
            ->setPairing(false)
            ->addApp($exampleApp)
        ;

        $exampleApp->createHost();
        $user->addServer($server);

        $this->addReference(self::SERVER_REFERENCE, $server);

        $manager->persist($user);
        $manager->flush();
    }
}
