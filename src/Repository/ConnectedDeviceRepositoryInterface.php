<?php

namespace App\Repository;

use App\Entity\ConnectedDevice;
use App\Entity\Server;

interface ConnectedDeviceRepositoryInterface
{
    public function getDeviceByHash(string $hash): ?ConnectedDevice;

    public function getDeviceByAccessCode(string $accessCode): ?ConnectedDevice;

    /**
     * @return ConnectedDevice[]
     */
    public function getLastActiveDevices(): array;

    public function removeNonPairedConnectedDevice(Server $server): bool;
}
