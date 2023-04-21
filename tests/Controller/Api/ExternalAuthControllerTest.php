<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\MainFixtures;
use App\Entity\ConnectedDevice;
use App\Entity\Host;
use App\Repository\HostRepository;
use App\Service\EncryptionService;
use App\Tests\FixtureAwareWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @covers \App\Controller\Api\ExternalAuthController
 */
class ExternalAuthControllerTest extends FixtureAwareWebTestCase
{
    private HostRepository $hostRepository;
    private EncryptionService $encryptionService;

    private string $trustedCookieName;

    public function setUp(): void
    {
        parent::setUp();

        $this->addFixture(new MainFixtures());
        $this->executeFixtures();

        $this->hostRepository = static::getContainer()->get(HostRepository::class);
        $this->encryptionService = static::getContainer()->get(EncryptionService::class);
        $this->trustedCookieName = static::getContainer()->getParameter('trusted_device_cookie_name');
    }

    public function testRequestAuthenticationFailedAction(): void
    {
        $response = $this->request('nick.devyour.cloud', '/', '127.0.0.1');

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        /** @var Host $host */
        $host = $this->hostRepository->findOneBy(['domain' => 'nick.devyour.cloud']);
        $server = $host->getServer();

        self::assertNotNull($server);
        self::assertEquals(0, $server->getConnectedDevices()->count());
    }

    public function testRequestAuthenticationFailedWithTokenAction(): void
    {
        $response = $this->request('nick.devyour.cloud', '/', '127.0.0.1', 'WRONG TOKEN');

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $host = $this->hostRepository->findOneByDomain('nick.devyour.cloud');
        $server = $host->getServer();
        self::assertNotNull($server);
        self::assertEquals(0, $server->getConnectedDevices()->count());
    }

    public function testRequestAuthenticationSuccessAction(): void
    {
        // Given
        $host = $this->hostRepository->findOneByDomain('symfony-request.devyour.cloud');
        $server = $host->getServer();

        self::assertEquals(1, $server->getConnectedDevices()->count());
        $connectedDevice = $server->getConnectedDevices()->first();

        $token = $this->encryptionService->createTrustedDeviceToken($connectedDevice);

        // When
        $response = $this->request('symfony-request.devyour.cloud', '/', '127.0.0.1', $token);

        // Then
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $em->refresh($server);

        self::assertEquals(1, $server->getConnectedDevices()->count());

        /** @var ConnectedDevice $connectedDevice */
        $connectedDevice = $server->getConnectedDevices()->first();
        self::assertTrue($connectedDevice->isActive());
    }

    public function testRequestPairingAction(): void
    {
        // Given
        $host = $this->hostRepository->findOneByDomain('pairing-request.devyour.cloud');
        $server = $host->getServer();
        self::assertEquals(0, $server->getConnectedDevices()->count());

        // When
        $host = 'pairing-request.devyour.cloud';
        $path = '/';
        $response = $this->request($host, $path, '127.0.0.1');

        // Then
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $em->refresh($server);

        self::assertEquals(1, $server->getConnectedDevices()->count());

        $cookie = $this->client->getCookieJar()->get($this->trustedCookieName);
        self::assertNotNull($cookie);
    }

    private function request($host, $uri, $ip, $token = null): Response
    {
        $params = [
            'HTTP_X-FORWARDED-METHOD' => 'GET',
            'HTTP_X-FORWARDED-PROTO' => 'https',
            'HTTP_X-FORWARDED-HOST' => $host,
            'HTTP_X-FORWARDED-URI' => $uri,
            'HTTP_X-FORWARDED-FOR' => $ip,
        ];

        if ($token) {
            $this->client->getCookieJar()->set(
                new BrowserKitCookie($this->trustedCookieName, \urlencode($token), strtotime('+1 day'))
            );
        }

        $this->client->request('GET', '/auth', [], [], $params);

        return $this->client->getResponse();
    }
}
