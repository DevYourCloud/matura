<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

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
    protected ?int $id = null;

    #[ORM\Column(type: 'string')]
    protected ?string $name = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'servers')]
    protected ?UserInterface $user = null;

    #[ORM\OneToOne(targetEntity: Host::class, mappedBy: 'server', cascade: ['persist', 'remove'])]
    protected ?Host $host = null;

    #[ORM\Column(type: 'boolean')]
    private bool $pairing = false;

    #[ORM\OneToMany(targetEntity: Application::class, mappedBy: 'server', cascade: ['persist', 'remove'])]
    private Collection $apps;

    #[ORM\OneToMany(targetEntity: ConnectedDevice::class, mappedBy: 'server', cascade: ['persist', 'remove'])]
    private Collection $connectedDevices;

    #[ORM\OneToMany(targetEntity: AccessToken::class, mappedBy: 'server', cascade: ['persist', 'remove'])]
    private Collection $tokens;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->apps = new ArrayCollection();
        $this->connectedDevices = new ArrayCollection();
        $this->tokens = new ArrayCollection();
        $this->active = true;
    }

    public function __toString(): string
    {
        return sprintf('#%d - %s', $this->getId(), $this->getName());
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

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

    /** @return Collection<int, Application> */
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

    /**
     * @return Collection<int, ConnectedDevice>
     */
    public function getConnectedDevices(): Collection
    {
        return $this->connectedDevices;
    }

    public function addConnectedDevice(ConnectedDevice $connectedDevice): self
    {
        if (!$this->connectedDevices->contains($connectedDevice)) {
            $this->connectedDevices->add($connectedDevice);
            $connectedDevice->setServer($this);
            $connectedDevice->setUser($this->user);
        }

        return $this;
    }

    public function removeConnectedDevice(ConnectedDevice $connectedDevice): self
    {
        throw new \Exception('Not yet implemented');
        // if ($this->connectedDevices->contains($connectedDevice)) {
        //     $this->connectedDevices->remove($connectedDevice);
        // }

        // return $this;
    }

    /**
     * @param ConnectedDevice[] $connectedDevices
     */
    public function setConnectedDevices(array $connectedDevices): self
    {
        $this->connectedDevices = new ArrayCollection($connectedDevices);

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

    public function getTokens(): Collection
    {
        return $this->tokens;
    }

    public function setTokens(Collection $tokens): self
    {
        $this->tokens = $tokens;

        return $this;
    }
}
