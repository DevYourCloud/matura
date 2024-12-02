<?php

namespace App\Service;

use App\Entity\ConnectedDevice;
use App\Exception\DecodingTokenFailed;
use App\Repository\ConnectedDeviceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ConnectedDeviceManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private EncryptionService $encryptionService,
        private ConnectedDeviceRepositoryInterface $connectedDeviceRepository,
        private LoggerInterface $logger,
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

    public function updateDeviceValidity(ConnectedDevice $device): bool
    {
        $isTokenValid = $this->checkDeviceValidity($device);
        if ($isTokenValid) {
            $device->setLastAccessed(new \DateTime('now'));
        } else {
            $device->setAccessCode($this->encryptionService->createAccessCode());
            $device->setActive(false);
        }

        $this->em->flush();

        return $isTokenValid;
    }

    public function checkDeviceValidity(ConnectedDevice $device): bool
    {
        $now = new \DateTime('now');
        $now->setTime(0, 0);

        $validityDate = $this->encryptionService->getTokenExpirationDate($device->getLastAccessed());

        return $validityDate >= $now;
    }
}
