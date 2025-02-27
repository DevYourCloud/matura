<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'application')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Application
{
    use TimeableTrait;
    use ActivableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string')]
    protected ?string $name = null;

    #[ORM\Column(type: 'string', nullable: false)]
    protected ?string $alias = null;

    #[ORM\Column(type: 'integer')]
    protected ?int $port = null;

    #[ORM\ManyToOne(targetEntity: Server::class, inversedBy: 'apps')]
    protected ?Server $server = null;

    #[ORM\OneToOne(targetEntity: Host::class, mappedBy: 'app', cascade: ['persist', 'remove'])]
    protected ?Host $host = null;

    public function __construct()
    {
        $this->active = true;
    }

    public function __toString(): string
    {
        return sprintf('#%d - %s (%s)', $this->getId(), $this->getName(), $this->getAlias());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer(Server $server): self
    {
        $this->server = $server;
        $this->createHost();

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function getHost(): ?Host
    {
        return $this->host;
    }

    public function setHost(Host $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function createHost(): self
    {
        $appDomain = $this->alias.'.'.$this->server->getHost()->getDomain();

        $host = $this->host;
        if (null === $host) {
            $host = new Host();
            $host->setCreatedAt(new \DateTime('now'));
        }

        $host->setDomain($appDomain);
        $host->setApp($this);
        $host->setUpdatedAt(new \DateTime('now'));

        $this->host = $host;

        return $this;
    }
}
