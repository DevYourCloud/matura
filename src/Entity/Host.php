<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Table(name: 'host')]
#[ORM\Entity(repositoryClass: 'App\Repository\HostRepository')]
#[ORM\HasLifecycleCallbacks]
class Host
{
    use TimeableTrait;

    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id;

    #[ORM\Column(type: 'string', unique: true)]
    private ?string $domain;

    #[ORM\ManyToOne(targetEntity: 'Server', inversedBy: 'host')]
    private ?Server $server = null;

    #[ORM\ManyToOne(targetEntity: 'Application', inversedBy: 'host')]
    private ?Application $app = null;

    public function __construct()
    {
    }

    public function __toString()
    {
        return $this->domain;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer(Server $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function getApp(): ?Application
    {
        return $this->app;
    }

    public function setApp(Application $app): self
    {
        $this->app = $app;

        return $this;
    }
}
