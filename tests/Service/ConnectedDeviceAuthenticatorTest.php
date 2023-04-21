<?php

namespace App\Tests\Service;

use App\DataFixtures\MainFixtures;
use App\Entity\ConnectedDevice;
use App\Model\ForwardedRequest;
use App\Repository\HostRepository;
use App\Security\ConnectedDeviceAuthenticator;
use App\Tests\FixtureAwareTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @covers \ConnectedDeviceAuthenticator
 */
class ConnectedDeviceAuthenticatorTest extends FixtureAwareTestCase
{
    private ConnectedDeviceAuthenticator $connectedDeviceAuthenticator;
    private HostRepository $hostRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->addFixture(new MainFixtures());
        $this->executeFixtures();
        $this->connectedDeviceAuthenticator = static::getContainer()->get(ConnectedDeviceAuthenticator::class);
        $this->hostRepository = static::getContainer()->get(HostRepository::class);
    }

    public function testAuthorizedDevice(): void
    {
        $domain = 'pairing-request.devyour.cloud';
        $host = $this->hostRepository->findOneByDomain($domain);
        $server = $host->getServer();

        $forwardedRequest = new ForwardedRequest($this->getRequest(
            $domain,
            '100.111.222.333',
            'Firefox'
        ));

        $connectedDevice = $this->connectedDeviceAuthenticator->getNewDevice($server, $forwardedRequest);

        self::assertInstanceOf(ConnectedDevice::class, $connectedDevice);
        self::assertTrue($connectedDevice->isActive());
    }

    // public function testUnAuthorizedDevice(): void
    // {
    //     $domain = 'unauthorized.devyour.cloud';
    //     $host = $this->hostRepository->findOneByDomain($domain);
    //     $server = $host->getServer();

    //     $forwardedRequest = new ForwardedRequest($this->getRequest(
    //         $domain,
    //         '999.111.222.333',
    //         'Firefox'
    //     ));

    //     $connectedDevice = $this->connectedDeviceAuthenticator->getNewDevice($server, $forwardedRequest);

    //     self::assertNull($connectedDevice);
    // }

    private function getRequest(string $host, string $ip, string $userAgent): Request
    {
        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $host,
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => $ip,
            'User-Agent' => $userAgent,
        ]);

        return $request;
    }
}
