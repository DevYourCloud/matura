<?php

namespace App\Tests\Mock;

use App\Entity\Host;
use App\Repository\HostRepositoryInterface;

class HostRepositoryMock implements HostRepositoryInterface
{
    private ?Host $host = null;

    public function setHost(Host $host): void
    {
        $this->host = $host;
    }

    public function findOneByDomain(string $domain): ?Host
    {
        return $this->host;
    }
}
