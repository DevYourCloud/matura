<?php

namespace App\Tests\Builder;

use App\Entity\AccessToken;
use App\Entity\Server;

class AccessTokenEntityBuilder
{
    private ?string $token = null;

    private ?Server $server = null;

    private ?\DateTime $validity = null;

    private ?bool $active = null;

    public static function create(): self
    {
        return new self();
    }

    public function build(): AccessToken
    {
        $accessToken = new AccessToken();

        if (null !== $this->token) {
            $accessToken->setAccessToken($this->token);
        }

        if (null !== $this->server) {
            $accessToken->setServer($this->server);
        }

        if (null !== $this->validity) {
            $accessToken->setValidity($this->validity);
        }

        if (null !== $this->active) {
            $accessToken->setActive($this->active);
        }

        return $accessToken;
    }

    public function withToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function withServer(Server $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function withValidity(?\DateTime $validity): self
    {
        $this->validity = $validity;

        return $this;
    }

    public function withActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
