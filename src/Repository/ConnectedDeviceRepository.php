<?php

namespace App\Repository;

use App\Entity\ConnectedDevice;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ConnectedDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConnectedDevice::class);
    }

    public function findByUserQuery(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.server', 's')
            ->andWhere('s.user = :user')
            ->setParameter(':user', $user)
        ;
    }

    public function findByAccessCode(string $accessCode): ?ConnectedDevice
    {
        return $this->createQueryBuilder('c')
            ->where('c.accessCode = :accessCode')
            ->setParameter(':accessCode', $accessCode)
            ->getQuery()->getOneOrNullResult()
        ;
    }
}
