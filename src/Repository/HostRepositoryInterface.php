<?php

namespace App\Repository;

use App\Entity\Host;

interface HostRepositoryInterface
{
    public function findOneByDomain(string $domain): ?Host;
}
