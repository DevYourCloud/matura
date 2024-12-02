<?php

namespace App\Tests\Builder;

use App\Entity\ConnectedDevice;
use App\Entity\Server;
use App\Entity\User;

class ConnectedDeviceEntityBuilder
{
    private ?Server $server = null;
    private ?string $hash = null;
    private ?string $accessCode = null;
    private ?bool $active = false;
    private ?User $user = null;
    private ?\DateTime $lastAccessedDate = null;

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

        if (null !== $this->user) {
            $connectedDevice->setUser($this->user);
        }

        if (null !== $this->lastAccessedDate) {
            $connectedDevice->setLastAccessed($this->lastAccessedDate);
        }

        $connectedDevice
            ->setActive($this->active)
            ->setIp('XX.YY.ZZ.LOL')
            ->setUserAgent('UserAgent')
            ->setCreatedAt(new \DateTime('now'))
            ->setAccessCodeGeneratedAt(new \DateTime('now'))
            ->setActive($this->active)
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

    public function withActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function withUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function withLastAccessedDate(\DateTime $date): self
    {
        $this->lastAccessedDate = $date;

        return $this;
    }
}
