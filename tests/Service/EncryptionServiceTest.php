<?php

namespace App\Tests\Service;

use App\Entity\ConnectedDevice;
use App\Entity\Host;
use App\Entity\Server;
use App\Service\EncryptionService;
use App\Tests\Builder\ServiceBuilder;
use PHPUnit\Framework\TestCase;

class EncryptionServiceTest extends TestCase
{
    private EncryptionService $encryptionService;

    public function setUp(): void
    {
        $this->encryptionService = ServiceBuilder::getEncryptionService();
    }

    public function testDeviceHash(): void
    {
        $ip = '123.123.123.123';
        $userAgent = 'Custom UserAgent';
        $host = new Host();
        $host->setDomain('slug.devyour.cloud');

        $server = new Server();
        $server->setHost($host);

        $connectedDevice = (new ConnectedDevice())
            ->setIp($ip)
            ->setUserAgent($userAgent)
            ->setServer($server)
            ->setCreatedAt(new \DateTime('now'))
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

        $host = new Host();
        $host->setDomain('new-slug.devyour.cloud');
        $server->setHost($host);

        $differentHash = $this->encryptionService->createConnectedDeviceHash($connectedDevice);

        self::assertNotEquals($hash, $differentHash);
    }

    public function testSecureTokenWithoutHash(): void
    {
        $this->expectException(\Exception::class);

        $ip = '123.123.123.123';
        $userAgent = 'Custom UserAgent';

        $host = new Host();
        $host->setDomain('slug.devyour.cloud');

        $server = new Server();
        $server->setHost($host);

        $connectedDevice = (new ConnectedDevice())
            ->setIp($ip)
            ->setUserAgent($userAgent)
            ->setServer($server)
            ->setCreatedAt(new \DateTime('now'))
        ;

        $this->encryptionService->createTrustedDeviceToken($connectedDevice);
    }

    public function testSecureTokenHash(): void
    {
        $ip = '123.123.123.123';
        $userAgent = 'Custom UserAgent';

        $host = new Host();
        $host->setDomain('slug.devyour.cloud');

        $server = new Server();
        $server->setHost($host);

        $connectedDevice = (new ConnectedDevice())
            ->setIp($ip)
            ->setUserAgent($userAgent)
            ->setServer($server)
            ->setCreatedAt(new \DateTime('now'))
        ;

        $hash = $this->encryptionService->createConnectedDeviceHash($connectedDevice);
        $connectedDevice->setHash($hash);

        $token = $this->encryptionService->createTrustedDeviceToken($connectedDevice);

        self::assertNotEmpty($token);

        $decodedToken = $this->encryptionService->decodeTrustedDeviceToken($token);

        self::assertEquals($hash, $decodedToken);
    }
}
