<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'access_token')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: "uniq_access_token", columns: ["access_token"])]
class AccessToken
{
    use TimeableTrait;
    use ActivableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    protected ?int $validityPeriod = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $validity = null;

    #[ORM\Column(type: 'string')]
    protected ?string $name = null;

    #[ORM\Column(type: 'string')]
    protected ?string $accessToken = null;

    #[ORM\ManyToOne(targetEntity: Server::class, inversedBy: 'tokens')]
    protected ?Server $server = null;

    public function __construct()
    {
        $this->active = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getValidity(): ?\DateTime
    {
        return $this->validity;
    }

    public function setValidity(?\DateTime $validity): self
    {
        $this->validity = $validity;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer(?Server $server): AccessToken
    {
        $this->server = $server;

        return $this;
    }

    public function getValidityPeriod(): ?int
    {
        return $this->validityPeriod;
    }

    public function setValidityPeriod(?int $validityPeriod): AccessToken
    {
        $this->validityPeriod = $validityPeriod;

        return $this;
    }
}
