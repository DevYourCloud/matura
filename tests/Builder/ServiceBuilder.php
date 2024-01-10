<?php

namespace App\Tests\Builder;

use App\Context\AppContext;
use App\EventListener\TrustedDeviceCookieEventListener;
use App\Factory\ConnectedDeviceFactory;
use App\Repository\ConnectedDeviceRepositoryInterface;
use App\Repository\HostRepositoryInterface;
use App\Service\ConnectedDeviceManager;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;

class ServiceBuilder
{
    public static function getEncryptionService(string $expirationDelay = '30'): EncryptionService
    {
        return new EncryptionService('SALT', 'SECRET_KEY', '1', $expirationDelay);
    }

    public static function getTrustedDeviceCookieListener(
        AppContext $appContext,
        EncryptionService $encryptionService,
        ConnectedDeviceFactory $connectedDeviceFactory,
        $trustedCookieName = '_truster_device'
    ): TrustedDeviceCookieEventListener {
        return new TrustedDeviceCookieEventListener(
            $appContext,
            $encryptionService,
            new NullLogger(),
            $connectedDeviceFactory,
            $trustedCookieName
        );
    }

    public static function getConnectedDeviceManager(
        ConnectedDeviceRepositoryInterface $connectedDeviceRepository,
        EncryptionService $encryptionService
    ): ConnectedDeviceManager {
        return new ConnectedDeviceManager($encryptionService, $connectedDeviceRepository, new NullLogger());
    }

    public static function getConnectedDeviceFactory(EncryptionService $encryptionService, EntityManagerInterface $em): ConnectedDeviceFactory
    {
        return new ConnectedDeviceFactory($encryptionService, $em);
    }

    public static function getAppContext(HostRepositoryInterface $hostRepository): AppContext
    {
        return new AppContext($hostRepository);
    }
}
