<?php

namespace App\Tests\Service;

use App\Entity\ConnectedDevice;
use App\Entity\Server;
use App\Service\EncryptionService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @covers \EncryptionService
 */
class EncryptionServiceTest extends KernelTestCase
{
    private EncryptionService $encryptionService;

    public function setUp(): void
    {
        $this->encryptionService = static::getContainer()->get(EncryptionService::class);
    }

    public function testDeviceHash(): void
    {
        $ip = '123.123.123.123';
        $userAgent = 'Custom UserAgent';
        $server = new Server();
        $server->createHost('slug.devyour.cloud');

        $connectedDevice = (new ConnectedDevice())
            ->setIp($ip)
            ->setUserAgent($userAgent)
            ->setServer($server)
        ;

        $hash = $this->encryptionService->createConnectedDeviceHash($connectedDevice);

        self::assertNotEmpty($hash);

        $newHash = $this->encryptionService->createConnectedDeviceHash($connectedDevice);
        self::assertEquals($hash, $newHash);

        $connectedDevice->setIp('123.123.123.124');
        $differentHash = $this->encryptionService->createConnectedDeviceHash($connectedDevice);

        self::assertNotEquals($hash, $differentHash);

        $connectedDevice->setUserAgent('Test UserAgent');
        $differentHash = $this->encryptionService->createConnectedDeviceHash($connectedDevice);

        self::assertNotEquals($hash, $differentHash);

        $server->createHost('new-slug.devyour.cloud');
        $differentHash = $this->encryptionService->createConnectedDeviceHash($connectedDevice);

        self::assertNotEquals($hash, $differentHash);
    }

    public function testSecureTokenWithoutHash(): void
    {
        $this->expectException(\Exception::class);

        $ip = '123.123.123.123';
        $userAgent = 'Custom UserAgent';
        $server = new Server();
        $server->createHost('slug.devyour.cloud');

        $connectedDevice = (new ConnectedDevice())
            ->setIp($ip)
            ->setUserAgent($userAgent)
            ->setServer($server)
        ;

        $this->encryptionService->createTrustedDeviceToken($connectedDevice);
    }

    public function testSecureTokenHash(): void
    {
        $ip = '123.123.123.123';
        $userAgent = 'Custom UserAgent';
        $server = new Server();
        $server->createHost('slug.devyour.cloud');

        $connectedDevice = (new ConnectedDevice())
            ->setIp($ip)
            ->setUserAgent($userAgent)
            ->setServer($server)
        ;

        $hash = $this->encryptionService->createConnectedDeviceHash($connectedDevice);
        $connectedDevice->setHash($hash);

        $token = $this->encryptionService->createTrustedDeviceToken($connectedDevice);

        self::assertNotEmpty($token);

        $decodedToken = $this->encryptionService->decodeTrustedDeviceToken($token);

        self::assertEquals($hash, $decodedToken);
    }
}
