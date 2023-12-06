<?php

namespace App\Security;

use App\Entity\ConnectedDevice;
use App\Entity\Server;
use App\Model\ForwardedRequest;
use App\Repository\ConnectedDeviceRepository;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ConnectedDeviceAuthenticator
{
    public function __construct(
        private ConnectedDeviceRepository $connectedDeviceRepository,
        private EncryptionService $encryptionService,
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    public function getNewDevice(Server $server, ForwardedRequest $request): ?ConnectedDevice
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

        $connectedDevice->setHash($this->getDeviceHash($connectedDevice));

        $this->em->persist($connectedDevice);

        $this->logger->info(sprintf(
            '[DEVICE AUTH] Pairing active, creating a new device for request %s - %s',
            $request->getForwardedIp(),
            $request->getUserAgent()
        ));

        $connectedDevice->setLastAccessed(new \DateTime('now'));

        // Disable pairing mode after new device was added
        // $server->setPairing(false);

        $this->em->flush();

        return $connectedDevice;
    }

    private function getDeviceHash(ConnectedDevice $connectedDevice): string
    {
        try {
            return $this->encryptionService->createConnectedDeviceHash($connectedDevice);
        } catch (\Exception $e) {
            throw new \Exception('Unable to create device hash : '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
