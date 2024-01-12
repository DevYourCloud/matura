<?php

namespace App\Tests\Mock;

use App\Entity\ConnectedDevice;
use App\Repository\ConnectedDeviceRepositoryInterface;

class ConnectedDeviceRepositoryMock implements ConnectedDeviceRepositoryInterface
{
    /** @var ConnectedDevice[] */
    private array $devices = [];

    public function addDevice(ConnectedDevice $connectedDevice): void
    {
        $this->devices[] = $connectedDevice;
    }

    public function getDeviceByHash(string $hash): ?ConnectedDevice
    {
        foreach ($this->devices as $device) {
            if ($device->getHash() === $hash) {
                return $device;
            }
        }

        return null;
    }

    public function getDeviceByAccessCode(string $accessCode): ?ConnectedDevice
    {
        foreach ($this->devices as $device) {
            if ($device->getAccessCode() === $accessCode) {
                return $device;
            }
        }

        return null;
    }

    public function getLastActiveDevices(): array
    {
        return $this->devices;
    }
}
