<?php

namespace App\Tests\Builder;

use App\Entity\Application;
use App\Entity\Host;
use App\Entity\Server;

class HostEntityBuilder
{
    private ?string $domain = null;
    private ?Server $server = null;
    private ?Application $app = null;

    public static function create(): self
    {
        return new self();
    }

    public function build(): Host
    {
        $host = new Host();

        if (null !== $this->server) {
            $host->setServer($this->server);
        }

        if (null !== $this->app) {
            $host->setApp($this->app);
        }

        $host
            ->setDomain($this->domain)
            ->setCreatedAt(new \DateTime('now'))
            ->setUpdatedAt(new \DateTime('now'))
        ;

        return $host;
    }

    public function withServer(Server $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function withApp(Application $application): self
    {
        $this->app = $application;

        return $this;
    }

    public function withDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }
}
