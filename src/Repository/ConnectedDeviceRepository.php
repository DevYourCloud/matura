<?php

namespace App\Repository;

use App\Entity\ConnectedDevice;
use App\Entity\Server;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConnectedDeviceRepository extends ServiceEntityRepository implements ConnectedDeviceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConnectedDevice::class);
    }

    public function getDeviceByHash(string $hash): ?ConnectedDevice
    {
        return $this->findOneBy(['hash' => $hash]);
    }

    public function getDeviceByAccessCode(string $accessCode): ?ConnectedDevice
    {
        return $this->createQueryBuilder('c')
            ->where('c.accessCode = :accessCode')
            ->setParameter(':accessCode', $accessCode)
            ->getQuery()->getOneOrNullResult()
        ;
    }

    public function getLastActiveDevices(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.lastAccessed is not NULL')
            ->andWhere('c.active = 1')
            ->orderBy('c.lastAccessed', 'DESC')
            ->setMaxResults(10)
            ->getQuery()->getResult()
        ;
    }

    public function removeNonPairedConnectedDevice(Server $server): bool
    {
        $this->createQueryBuilder('c')
            ->delete()
            ->where('c.active = 0')
            ->andWhere('c.server', $server)
            ->getQuery()->execute()
        ;

        return true;
    }
}
