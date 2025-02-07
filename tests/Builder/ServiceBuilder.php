<?php

namespace App\Tests\Builder;

use App\Context\AppContext;
use App\EventListener\OnKernelRequestCreateDevice;
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
    public static function getEncryptionService(int $expirationDelay = 30): EncryptionService
    {
        return new EncryptionService('SALT', 'SECRET_KEY', '1', $expirationDelay);
    }

    public static function getTrustedDeviceCookieListener(
        AppContext $appContext,
        EncryptionService $encryptionService,
        string $trustedCookieName = '_truster_device',
        int $cookieLifetime = 30,
    ): TrustedDeviceCookieEventListener {
        return new TrustedDeviceCookieEventListener(
            $appContext,
            $encryptionService,
            $trustedCookieName,
            $cookieLifetime
        );
    }

    public static function getOnKernelRequestCreateDeviceListener(
        AppContext $appContext,
        ConnectedDeviceFactory $connectedDeviceFactory,
    ): OnKernelRequestCreateDevice {
        return new OnKernelRequestCreateDevice(
            $appContext,
            $connectedDeviceFactory,
            new NullLogger()
        );
    }

    public static function getConnectedDeviceManager(
        EntityManagerInterface $em,
        ConnectedDeviceRepositoryInterface $connectedDeviceRepository,
        EncryptionService $encryptionService,
    ): ConnectedDeviceManager {
        return new ConnectedDeviceManager($em, $encryptionService, $connectedDeviceRepository, new NullLogger());
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
