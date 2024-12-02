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
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ConnectedDeviceManagerTest extends TestCase
{
    public const TOKEN_DELAY = 10;

    private EncryptionService $encryptionService;

    private ConnectedDeviceRepositoryMock $connectedDeviceRepository;

    private ConnectedDeviceManager $connectedDeviceManager;

    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        $this->encryptionService = ServiceBuilder::getEncryptionService(self::TOKEN_DELAY);
        $this->connectedDeviceRepository = new ConnectedDeviceRepositoryMock();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->connectedDeviceManager = new ConnectedDeviceManager(
            $this->entityManager,
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

    protected function tokenValidityProvider(): array
    {
        return [
            'valid date yesterday' => [new \DateInterval('P1D'), true],
            'valid date 5 days ago' => [new \DateInterval('P5D'), true],
            'valid date 10 days ago' => [new \DateInterval('P'.self::TOKEN_DELAY.'D'), true],
            'invalid date 11 days ago' => [new \DateInterval('P11D'), false],
            'invalid date 20 days ago' => [new \DateInterval('P20D'), false],
        ];
    }

    /**
     * @dataProvider tokenValidityProvider
     */
    public function testCheckDeviceValidity(\DateInterval $dateInterval, bool $valid): void
    {
        // Given
        $date = new \DateTime('now');
        $date->sub($dateInterval);

        $host = HostEntityBuilder::create()->withDomain('test.com')->build();
        $server = ServerEntityBuilder::create()->withHost($host)->build();
        $connectedDevice = ConnectedDeviceEntityBuilder::create()
            ->withHash('HASH_TEST')
            ->withServer($server)
            ->withLastAccessedDate($date)
            ->build()
        ;

        // Then
        self::assertEquals($date, $connectedDevice->getLastAccessed());
        self::assertEquals($valid, $this->connectedDeviceManager->checkDeviceValidity($connectedDevice));
    }

    public function testUpdateDeviceValidityWithValidToken(): void
    {
        $originalDate = new \DateTime('yesterday');

        $host = HostEntityBuilder::create()->withDomain('test.com')->build();
        $server = ServerEntityBuilder::create()->withHost($host)->build();
        $connectedDevice = ConnectedDeviceEntityBuilder::create()
            ->withHash('HASH_TEST')
            ->withServer($server)
            ->withLastAccessedDate(clone $originalDate)
            ->withActive(true)
            ->build()
        ;

        // When
        $this->connectedDeviceManager->updateDeviceValidity($connectedDevice);

        // Then
        self::assertEquals((new \DateTime('now'))->format('Y-m-d H:i'), $connectedDevice->getLastAccessed()->format('Y-m-d H:i'));
        self::assertTrue($connectedDevice->isActive());
    }

    public function testUpdateDeviceValidityWithInvalidToken(): void
    {
        $date = new \DateTime('yesterday');
        $date->sub(new \DateInterval('P20D'));

        $host = HostEntityBuilder::create()->withDomain('test.com')->build();
        $server = ServerEntityBuilder::create()->withHost($host)->build();
        $connectedDevice = ConnectedDeviceEntityBuilder::create()
            ->withHash('HASH_TEST')
            ->withServer($server)
            ->withLastAccessedDate($date)
            ->build()
        ;

        $this->connectedDeviceManager->updateDeviceValidity($connectedDevice);

        self::assertEquals($date, $connectedDevice->getLastAccessed());
        self::assertFalse($connectedDevice->isActive());
    }
}
