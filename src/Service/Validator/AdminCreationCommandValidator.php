<?php

namespace App\Service\Validator;

use function Symfony\Component\String\u;

class AdminCreationCommandValidator
{
    public function validatePassword(?string $plainPassword): string
    {
        if ($plainPassword === null || $plainPassword === '' || $plainPassword === '0') {
            throw new \InvalidArgumentException('The password can not be empty.');
        }

        if (u($plainPassword)->trim()->length() < 6) {
            throw new \InvalidArgumentException('The password must be at least 6 characters long.');
        }

        return $plainPassword;
    }

    public function validateEmail(?string $email): string
    {
        if ($email === null || $email === '' || $email === '0') {
            throw new \InvalidArgumentException('The email can not be empty.');
        }

        if (null === u($email)->indexOf('@')) {
            throw new \InvalidArgumentException('The email should look like a real email.');
        }

        return $email;
    }

    public function validateUsername(?string $username): string
    {
        if ($username === null || $username === '' || $username === '0') {
            throw new \InvalidArgumentException('The username can not be empty.');
        }

        return $username;
    }
}
