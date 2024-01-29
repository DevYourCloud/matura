<?php

namespace App\Tests\Builder;

use App\Entity\Host;
use App\Entity\Server;
use App\Entity\User;

class ServerEntityBuilder
{
    private ?Host $host = null;
    private bool $pairing = false;
    private ?User $user = null;

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

        if (null !== $this->user) {
            $server->setUser($this->user);
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

    public function withUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
