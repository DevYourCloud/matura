<?php

namespace App\Tests\Service;

use App\Entity\ConnectedDevice;
use App\Exception\DecodingTokenFailed;
use App\Service\ConnectedDeviceManager;
use App\Service\EncryptionService;
use App\Tests\Builder\ConnectedDeviceEntityBuilder;
use App\Tests\Builder\HostEntityBuilder;
use App\Tests\Builder\ServerEntityBuilder;
use App\Tests\Builder\ServiceBuilder;
use App\Tests\Mock\ConnectedDeviceRepositoryMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ConnectedDeviceManagerTest extends TestCase
{
    private EncryptionService $encryptionService;

    private ConnectedDeviceRepositoryMock $connectedDeviceRepository;

    private ConnectedDeviceManager $connectedDeviceManager;

    public function setUp(): void
    {
        $this->encryptionService = ServiceBuilder::getEncryptionService();
        $this->connectedDeviceRepository = new ConnectedDeviceRepositoryMock();

        $this->connectedDeviceManager = new ConnectedDeviceManager(
            $this->encryptionService,
            $this->connectedDeviceRepository,
            new NullLogger()
        );
    }

    public function testDecodeAndFindDevice(): void
    {
        // Given
        $host = HostEntityBuilder::create()->withDomain('test.com')->build();
        $server = ServerEntityBuilder::create()->withHost($host)->build();
        $connectedDevice = ConnectedDeviceEntityBuilder::create()
            ->withHash('HASH_TEST')
            ->withServer($server)
            ->build()
        ;

        $this->connectedDeviceRepository->addDevice($connectedDevice);
        $token = $this->encryptionService->createTrustedDeviceToken($connectedDevice);

        // When
        $deviceFound = $this->connectedDeviceManager->decodeAndFindConnectedDevice($token);

        self::assertInstanceOf(ConnectedDevice::class, $deviceFound);
        self::assertEquals($connectedDevice->getHash(), $deviceFound->getHash());
    }

    public function testDecodeAndFindDeviceWithWrongHash(): void
    {
        // Given
        ConnectedDeviceEntityBuilder::create()->withHash('HASH_TEST')->build();

        // When
        $this->expectException(DecodingTokenFailed::class);
        $this->connectedDeviceManager->decodeAndFindConnectedDevice('WRONG_HASH');
    }

    public function testValidateAccessCode(): void
    {
        // Given
        $connectedDevice = ConnectedDeviceEntityBuilder::create()
            ->withAccessCode('12345')
            ->withActive(false)
            ->build()
        ;

        $this->connectedDeviceRepository->addDevice($connectedDevice);

        // When
        $connectedDevice = $this->connectedDeviceManager->validateAccessCode('12345');

        self::assertInstanceOf(ConnectedDevice::class, $connectedDevice);
        self::assertNull($connectedDevice->getAccessCode());
        self::assertTrue($connectedDevice->isActive());
    }
}
