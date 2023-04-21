<?php

namespace App\DataFixtures;

use App\Entity\ConnectedDevice;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ConnectedDeviceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $authorizedDevice = new ConnectedDevice();
        $authorizedDevice
            ->setIp('100.111.222.333')
            ->setUserAgent('Firefox')
            ->setHash('e7adb9316a1588a2fe92131ac9f743e6a1de2b1c3ef21efd54b0f9edb0e0c8e0')
            ->setActive(true)
        ;

        $this->addReference(self::getReferenceKey('authorized'), $authorizedDevice);

        $symfonyRequestDevice = new ConnectedDevice();
        $symfonyRequestDevice
            ->setIp('127.0.0.1')
            ->setUserAgent('Symfony BrowserKit')
            ->setHash('c495b608302069d9d983e4dca718be3a7bcd55b0dfc542f20e00021d98dbaa97')
            ->setCreatedAt(new \DateTime('2021-01-01 12:00:00'))
            ->setActive(true)
        ;

        $this->addReference(self::getReferenceKey('symfony-request'), $symfonyRequestDevice);
    }

    public static function getReferenceKey(string $name): string
    {
        return 'fixtures_connected_device_'.$name;
    }
}
