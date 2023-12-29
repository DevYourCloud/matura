<?php

namespace App\Repository;

use App\Entity\ConnectedDevice;

interface ConnectedDeviceRepositoryInterface
{
    public function getDeviceByHash(string $hash): ?ConnectedDevice;

    public function getDeviceByAccessCode(string $accessCode): ?ConnectedDevice;

    public function getLastActiveDevices(): array;
}
