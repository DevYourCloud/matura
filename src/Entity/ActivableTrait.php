<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait ActivableTrait
{
    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $active = false;

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}
