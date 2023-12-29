<?php

namespace App\Tests\Builder;

use App\Entity\Application;

class ApplicationEntityBuilder
{
    private bool $active = false;

    public static function create(): self
    {
        return new self();
    }

    public function build(): Application
    {
        return (new Application())
            ->setActive($this->active)
            ->setCreatedAt(new \DateTime('now'))
        ;
    }
}
