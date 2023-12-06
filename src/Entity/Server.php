<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Table(name: 'server')]
#[ORM\Entity(repositoryClass: 'App\Repository\ServerRepository')]
#[ORM\HasLifecycleCallbacks]
class Server
{
    use TimeableTrait;
    use ActivableTrait;

    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id;

    #[ORM\Column(type: 'string')]
    protected ?string $name;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $description;

    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'servers')]
    protected ?User $user;

    #[ORM\OneToOne(targetEntity: 'Host', mappedBy: 'server', cascade: ['persist', 'remove'])]
    protected Host $host;

    #[ORM\Column(type: 'boolean')]
    private bool $pairing = false;

    #[ORM\OneToMany(targetEntity: 'Application', mappedBy: 'server', cascade: ['persist', 'remove'])]
    private Collection $apps;

    #[ORM\OneToMany(targetEntity: 'ConnectedDevice', mappedBy: 'server', cascade: ['persist', 'remove'])]
    private Collection $connectedDevices;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->apps = new ArrayCollection();
        $this->connectedDevices = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf('#%d - %s', $this->getId(), $this->getName());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): self
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getHost(): ?Host
    {
        return $this->host;
    }

    public function setHost(Host $host): self
    {
        $this->host = $host;
        $this->host->setServer($this);

        return $this;
    }

    public function getApps(): Collection
    {
        return $this->apps;
    }

    public function addApp(Application $app): self
    {
        if (!$this->apps->contains($app)) {
            $app->setServer($this);
            $this->apps->add($app);
        }

        return $this;
    }

    public function removeApp(Application $app): self
    {
        if ($this->apps->contains($app)) {
            $this->apps->remove($app);
        }

        return $this;
    }

    public function getConnectedDevices(): Collection
    {
        return $this->connectedDevices;
    }

    public function addConnectedDevices(ConnectedDevice $connectedDevice): self
    {
        if (!$this->connectedDevices->contains($connectedDevice)) {
            $this->connectedDevices->add($connectedDevice);
            $connectedDevice->setServer($this);
        }

        return $this;
    }

    public function removeConnectedDevices(ConnectedDevice $connectedDevice): self
    {
        throw new \Exception('Not yet implemented');
        // if ($this->connectedDevices->contains($connectedDevice)) {
        //     $this->connectedDevices->remove($connectedDevice);
        // }

        return $this;
    }

    public function setConnectedDevices($connectedDevices): self
    {
        $this->connectedDevices = $connectedDevices;

        return $this;
    }

    public function isPairing(): bool
    {
        return $this->pairing;
    }

    public function setPairing(bool $pairing): self
    {
        $this->pairing = $pairing;

        return $this;
    }
}
