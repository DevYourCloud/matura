<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Table(name: 'connected_device')]
#[ORM\Entity(repositoryClass: 'App\Repository\ConnectedDeviceRepository')]
#[ORM\HasLifecycleCallbacks]
class ConnectedDevice
{
    use TimeableTrait;
    use ActivableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id;

    #[ORM\Column(type: 'string')]
    protected string $ip;

    #[ORM\Column(type: 'string')]
    protected string $userAgent;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $hash = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $lastAccessed;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $accessCode = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $name = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $accessCodeGeneratedAt = null;

    #[ORM\ManyToOne(targetEntity: Server::class, inversedBy: 'connectedDevices')]
    protected Server $server;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'connectedDevices')]
    protected ?UserInterface $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function setServer(Server $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function getLastAccessed(): ?\DateTime
    {
        return $this->lastAccessed;
    }

    public function setLastAccessed(\DateTime $lastAccessed): self
    {
        $this->lastAccessed = $lastAccessed;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function hasAccessToApp(?Application $app): bool
    {
        if (!$this->user instanceof User) {
            return false;
        }

        return $this->user->isAdmin();
    }

    public function getAccessCode(): ?string
    {
        return $this->accessCode;
    }

    public function setAccessCode(?string $accessCode): self
    {
        $this->accessCode = $accessCode;

        return $this;
    }

    public function getAccessCodeGeneratedAt(): ?\DateTime
    {
        return $this->accessCodeGeneratedAt;
    }

    public function setAccessCodeGeneratedAt(\DateTime $accessCodeGeneratedAt): self
    {
        $this->accessCodeGeneratedAt = $accessCodeGeneratedAt;

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

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }
}
