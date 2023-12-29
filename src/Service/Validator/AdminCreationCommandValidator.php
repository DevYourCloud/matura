<?php

namespace App\Service\Validator;

use function Symfony\Component\String\u;

class AdminCreationCommandValidator
{
    public function validatePassword(?string $plainPassword): string
    {
        if (null === $plainPassword || '' === $plainPassword || '0' === $plainPassword) {
            throw new \InvalidArgumentException('The password can not be empty.');
        }

        if (u($plainPassword)->trim()->length() < 6) {
            throw new \InvalidArgumentException('The password must be at least 6 characters long.');
        }

        return $plainPassword;
    }

    public function validateEmail(?string $email): string
    {
        if (null === $email || '' === $email || '0' === $email) {
            throw new \InvalidArgumentException('The email can not be empty.');
        }

        if (null === u($email)->indexOf('@')) {
            throw new \InvalidArgumentException('The email should look like a real email.');
        }

        return $email;
    }

    public function validateUsername(?string $username): string
    {
        if (null === $username || '' === $username || '0' === $username) {
            throw new \InvalidArgumentException('The username can not be empty.');
        }

        return $username;
    }
}
