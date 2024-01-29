<?php

namespace App\Tests\Builder;

use App\Entity\User;

class UserEntityBuilder
{
    private $roles = [];

    public static function create(): self
    {
        return new self();
    }

    public function build(): User
    {
        $user = new User();

        if (count($this->roles) > 0) {
            $user->setRoles($this->roles);
        }

        return $user
            ->setEmail('test@test.fr')
            ->setFullName('Test')
            ->setActive(true)
            ->setCreatedAt(new \DateTime('now'))
        ;
    }

    public function withRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }
}
