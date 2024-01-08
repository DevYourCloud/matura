<?php

namespace App\Context;

use App\Entity\Application;
use App\Entity\ConnectedDevice;
use App\Entity\Server;
use App\Model\ForwardedRequest;
use App\Repository\HostRepositoryInterface;

class AppContext
{
    private ?Server $server = null;

    private ?Application $app = null;

    private ?ConnectedDevice $connectedDevice = null;

    private ?ForwardedRequest $forwardedRequest = null;

    private bool $accessGranted = false;

    private bool $createTrustedCookie = false;

    private bool $initialized = false;

    public function __construct(private HostRepositoryInterface $hostRepository)
    {
    }

    public function initializeFromRequest(ForwardedRequest $forwardedRequest): void
    {
        $this->forwardedRequest = $forwardedRequest;

        if (!$forwardedRequest->isValid()) {
            throw new \Exception(sprintf('Forward request invalid: %s', $forwardedRequest));
        }

        $host = $this->hostRepository->findOneByDomain($forwardedRequest->getForwardedHost());

        if (null === $host) {
            throw new \Exception(sprintf('Host not found: %s', $forwardedRequest->getForwardedHost()));
        }

        if (null !== $host->getServer()) {
            $this->server = $host->getServer();
        } elseif (null !== $host->getApp()) {
            $this->app = $host->getApp();
            $this->server = $host->getApp()->getServer();
        }

        if (null === $this->server) {
            throw new \Exception(sprintf('server inactive or not found: %s', $forwardedRequest->getForwardedHost()));
        }

        $this->initialized = true;
    }

    public function getServer(): Server
    {
        if (!$this->initialized) {
            throw new \Exception('AppContext not initialized, use initializeFromRequest');
        }

        return $this->server;
    }

    public function getApp(): ?Application
    {
        if (!$this->initialized) {
            throw new \Exception('AppContext not initialized, use initializeFromRequest');
        }

        return $this->app;
    }

    public function getForwardedRequest(): ForwardedRequest
    {
        if (!$this->initialized) {
            throw new \Exception('AppContext not initialized, use initializeFromRequest');
        }

        return $this->forwardedRequest;
    }

    public function getConnectedDevice(): ?ConnectedDevice
    {
        if (!$this->initialized) {
            throw new \Exception('AppContext not initialized, use initializeFromRequest');
        }

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

    public function createTrustedCookie(): bool
    {
        return $this->createTrustedCookie;
    }

    public function setCreateTrustedCookie(bool $createCookie): self
    {
        $this->createTrustedCookie = $createCookie;

        return $this;
    }

    public function hasValidForwardedAuthRequest(): bool
    {
        return $this->forwardedRequest && $this->forwardedRequest->isValid();
    }
}
