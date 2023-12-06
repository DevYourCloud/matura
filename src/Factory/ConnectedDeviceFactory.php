<?php

namespace App\Factory;

use App\Entity\ConnectedDevice;
use App\Entity\Server;
use App\Model\ForwardedRequest;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;

class ConnectedDeviceFactory
{
    public function __construct(
        private EncryptionService $encryptionService,
        private EntityManagerInterface $em
    ) {}

    public function build(ForwardedRequest $request, Server $server): ConnectedDevice
    {
        $connectedDevice = new ConnectedDevice();
        $connectedDevice
            ->setActive(true)
            ->setServer($server)
            ->setIp($request->getForwardedIp())
            ->setUserAgent($request->getUserAgent())
            ->setCreatedAt(new \DateTime('now'))
            ->setAccessCode($this->encryptionService->createAccessCode())
            ->setAccessCodeGeneratedAt(new \DateTime('now'))
        ;

        $connectedDevice->setHash($this->encryptionService->createConnectedDeviceHash($connectedDevice));

        $this->em->persist($connectedDevice);

        $connectedDevice->setLastAccessed(new \DateTime('now'));

        // Disable pairing mode after new device was added
        // $server->setPairing(false);

        $this->em->flush();

        return $connectedDevice;
    }
}
