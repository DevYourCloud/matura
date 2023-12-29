<?php

namespace App\Tests\Builder;

use App\Entity\ConnectedDevice;
use App\Entity\Server;

class ConnectedDeviceEntityBuilder
{
    private ?Server $server = null;
    private ?string $hash = null;
    private ?string $accessCode = null;

    public static function create(): self
    {
        return new self();
    }

    public function build(): ConnectedDevice
    {
        $connectedDevice = new ConnectedDevice();

        if (null !== $this->server) {
            $connectedDevice->setServer($this->server);
        }

        if (null !== $this->accessCode) {
            $connectedDevice->setAccessCode($this->accessCode);
        }

        if (null !== $this->hash) {
            $connectedDevice->setHash($this->hash);
        }

        $connectedDevice
            ->setActive(false)
            ->setIp('XX.YY.ZZ.LOL')
            ->setUserAgent('UserAgent')
            ->setCreatedAt(new \DateTime('now'))
            ->setAccessCodeGeneratedAt(new \DateTime('now'))
        ;

        return $connectedDevice;
    }

    public function withServer(Server $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function withHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function withAccessCode(string $accessCode): self
    {
        $this->accessCode = $accessCode;

        return $this;
    }
}
