<?php

namespace App\Context;

use App\Entity\Application;
use App\Entity\ConnectedDevice;
use App\Entity\Server;
use App\Model\ForwardedRequest;
use App\Repository\HostRepository;

class AppContext
{
    private ?Server $server = null;

    private ?Application $app = null;

    private ?ConnectedDevice $connectedDevice = null;

    private ?ForwardedRequest $forwardedRequest = null;

    private bool $accessGranted = false;

    public function __construct(private HostRepository $hostRepository)
    {
    }

    public function initializeFromRequest(ForwardedRequest $forwardedRequest): void
    {
        $this->forwardedRequest = $forwardedRequest;

        if (!$forwardedRequest->isValid()) {
            throw new \Exception(
                sprintf('Forward request invalid: %s', $forwardedRequest)
            );
        }

        $host = $this->hostRepository->findOneByDomain($forwardedRequest->getForwardedHost());

        if (null !== $host->getServer()) {
            $this->server = $host->getServer();
        }

        if (null !== $host->getApp()) {
            $this->app = $host->getApp();
            $this->server = $host->getApp()->getServer();
        }

        if (null === $this->server) {
            throw new \Exception(
                sprintf('server inactive or not found: %s', $forwardedRequest->getForwardedHost())
            );
        }
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    // private function setServer(Server $server): self
    // {
    //     $this->server = $server;

    //     return $this;
    // }

    public function getApp(): ?Application
    {
        return $this->app;
    }

    // private function setApp(?Application $app): self
    // {
    //     $this->app = $app;

    //     return $this;
    // }

    public function getForwardedRequest(): ?ForwardedRequest
    {
        return $this->forwardedRequest;
    }

    // private function setForwardedRequest(ForwardedRequest $forwardedRequest): self
    // {
    //     $this->forwardedRequest = $forwardedRequest;

    //     return $this;
    // }

    public function getConnectedDevice(): ?ConnectedDevice
    {
        return $this->connectedDevice;
    }

    public function setConnectedDevice(ConnectedDevice $connectedDevice): self
    {
        $this->connectedDevice = $connectedDevice;

        return $this;
    }

    public function isAccessGranted(): bool
    {
        return $this->accessGranted;
    }

    public function setAccessGranted(bool $accessGranted): self
    {
        $this->accessGranted = $accessGranted;

        return $this;
    }

    public function hasValidForwardedAuthRequest(): bool
    {
        return $this->forwardedRequest && $this->forwardedRequest->isValid();
    }
}
