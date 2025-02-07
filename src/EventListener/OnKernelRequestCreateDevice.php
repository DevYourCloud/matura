<?php

namespace App\EventListener;

use App\Context\AppContext;
use App\Entity\ConnectedDevice;
use App\Factory\ConnectedDeviceFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener]
class OnKernelRequestCreateDevice
{
    public function __construct(
        private AppContext $appContext,
        private ConnectedDeviceFactory $connectedDeviceFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->appContext->createTrustedCookie() || $this->appContext->getConnectedDevice() instanceof ConnectedDevice) {
            return;
        }

        $server = $this->appContext->getServer();

        if (!$server->isPairing()) {
            $this->logger->info(sprintf('Paring not active on server %s', $server->getName()));

            return;
        }

        // Creating new device
        $connectedDevice = $this->connectedDeviceFactory->build(
            $this->appContext->getForwardedRequest(),
            $this->appContext->getServer()
        );

        $this->appContext->setConnectedDevice($connectedDevice);
    }
}
