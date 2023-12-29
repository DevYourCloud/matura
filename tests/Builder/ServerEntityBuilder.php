<?php

namespace App\Tests\Builder;

use App\Entity\Host;
use App\Entity\Server;

class ServerEntityBuilder
{
    private ?Host $host = null;
    private bool $pairing = false;

    public static function create(): self
    {
        return new self();
    }

    public function build(): Server
    {
        $server = new Server();

        if (null !== $this->host) {
            $server->setHost($this->host);
        }

        return $server
            ->setName('Test Server')
            ->setDescription('This is a good server')
            ->setPairing($this->pairing)
        ;
    }

    public function withHost(Host $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function withPairing(bool $pairing): self
    {
        $this->pairing = $pairing;

        return $this;
    }
}
