<?php

namespace App\Service;

use App\Entity\ConnectedDevice;
use App\Exception\DecodingTokenFailed;
use App\Repository\ConnectedDeviceRepositoryInterface;
use Psr\Log\LoggerInterface;

class ConnectedDeviceManager
{
    public function __construct(
        private EncryptionService $encryptionService,
        private ConnectedDeviceRepositoryInterface $connectedDeviceRepository,
        private LoggerInterface $logger
    ) {
    }

    public function decodeAndFindConnectedDevice(string $encodedToken): ?ConnectedDevice
    {
        try {
            $decoded = $this->encryptionService->decodeTrustedDeviceToken($encodedToken);
        } catch (\Exception $e) {
            throw new DecodingTokenFailed(sprintf('[COOKIE AUTH] Error decrypting token : %s - %s', $e->getMessage(), $encodedToken));
        }

        $this->logger->debug(sprintf('Looking for a device with token "%s"', $decoded));

        return $this->connectedDeviceRepository->getDeviceByHash($decoded);
    }

    public function validateAccessCode(string $accessCode): ?ConnectedDevice
    {
        $connectedDevice = $this->connectedDeviceRepository->getDeviceByAccessCode($accessCode);

        if (null !== $connectedDevice) {
            $connectedDevice->setAccessCode(null);
            $connectedDevice->setActive(true);

            return $connectedDevice;
        }

        return null;
    }
}
