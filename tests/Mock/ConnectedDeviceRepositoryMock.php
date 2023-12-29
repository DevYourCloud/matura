<?php

namespace App\Tests\Mock;

use App\Entity\ConnectedDevice;
use App\Repository\ConnectedDeviceRepositoryInterface;

class ConnectedDeviceRepositoryMock implements ConnectedDeviceRepositoryInterface
{
    private ?ConnectedDevice $device = null;

    public function setDevice(ConnectedDevice $connectedDevice): void
    {
        $this->device = $connectedDevice;
    }

    public function getDeviceByHash(string $hash): ?ConnectedDevice
    {
        return $this->device;
    }

    public function getDeviceByAccessCode(string $accessCode): ?ConnectedDevice
    {
        return $this->device;
    }

    public function getLastActiveDevices(): array
    {
        return [$this->device];
    }
}
