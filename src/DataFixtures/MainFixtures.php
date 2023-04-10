<?php

namespace App\DataFixtures;

use App\Entity\Server;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MainFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user = $this->getReference(UserFixtures::getReferenceKey('nick'));
        $manager->persist($user);

        $admin = $this->getReference(UserFixtures::getReferenceKey('admin'));
        $manager->persist($admin);

        /** @var Server $server */
        $server = $this->getReference(ServerFixtures::getReferenceKey('nick'));
        $manager->persist($server);

        $unauthorized = $this->getReference(ServerFixtures::getReferenceKey('unauthorized'));
        $manager->persist($unauthorized);

        $authorized = $this->getReference(ServerFixtures::getReferenceKey('authorized'));
        $manager->persist($authorized);

        $symfonyRequest = $this->getReference(ServerFixtures::getReferenceKey('symfony-request'));
        $manager->persist($symfonyRequest);

        $pairingRequest = $this->getReference(ServerFixtures::getReferenceKey('pairing-request'));
        $manager->persist($pairingRequest);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ServerFixtures::class,
            UserFixtures::class,
        ];
    }
}
