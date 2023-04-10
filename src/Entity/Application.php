<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="application")
 *
 * @ORM\HasLifecycleCallbacks()
 */
class Application
{
    use TimeableTrait;
    use ActivableTrait;

    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    protected ?int $id;

    /**
     * @ORM\Column(type="string")
     */
    protected ?string $name;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected ?string $alias;

    /**
     * @ORM\Column(type="integer")
     */
    protected ?int $port;

    /**
     * @ORM\ManyToOne(targetEntity="Server", inversedBy="apps")
     */
    protected ?Server $server;

    /** @ORM\OneToOne(targetEntity="Host", mappedBy="app", cascade={"persist", "remove"}) */
    protected Host $host;

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
        $this->createHost($server->getHost()->getDomain());

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

    public function createHost(string $serverDomain): self
    {
        $appDomain = $this->alias.'.'.$serverDomain;

        $this->host = new Host();
        $this->host->setDomain($appDomain);
        $this->host->setApp($this);

        return $this;
    }
}
