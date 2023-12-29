<?php

namespace App\Repository;

use App\Entity\Host;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HostRepository extends ServiceEntityRepository implements HostRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Host::class);
    }

    public function findOneByDomain(string $domain): ?Host
    {
        return $this->findOneBy(['domain' => $domain]);
    }
}
