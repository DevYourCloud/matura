<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait TimeableTrait
{
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected ?\DateTime $createdAt = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?\DateTime $updatedAt = null;

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist(): void
    {
        if (null === $this->createdAt) {
            $this->createdAt = new \DateTime('now');
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime('now');
    }
}
