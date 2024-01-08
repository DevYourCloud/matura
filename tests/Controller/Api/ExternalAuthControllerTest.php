<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\DeviceAuthorizedFixture;
use App\DataFixtures\DeviceNotAuthorizedFixture;
use App\Entity\ConnectedDevice;
use App\Entity\Server;
use App\Repository\HostRepositoryInterface;
use App\Service\EncryptionService;
use App\Tests\FixtureAwareWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\HttpFoundation\Response;

class ExternalAuthControllerTest extends FixtureAwareWebTestCase
{
    private HostRepositoryInterface $hostRepository;

    private EncryptionService $encryptionService;

    private string $trustedCookieName;

    public function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();

        $this->hostRepository = $container->get(HostRepositoryInterface::class);
        $this->trustedCookieName = $container->getParameter('trusted_device_cookie_name');
        $this->encryptionService = $container->get(EncryptionService::class);
    }

    public function testRequestAuthenticationFailedAction(): void
    {
        // Given
        $fixture = new DeviceNotAuthorizedFixture();
        $this->addFixture($fixture);
        $this->executeFixtures();

        /** @var Server $server */
        $server = $fixture->getReference(DeviceNotAuthorizedFixture::SERVER_REFERENCE, Server::class);

        // When
        $response = $this->request($server->getHost()->getDomain(), '/', '127.0.0.1');
        $responseApp = $this->request($server->getHost()->getDomain(), '/exampleApp', '127.0.0.1');
        $responseWrongToken = $this->request($server->getHost()->getDomain(), '/', '127.0.0.1', 'WRONG TOKEN');

        // Then
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $responseApp->getStatusCode());
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $responseWrongToken->getStatusCode());
    }

    public function testRequestAuthenticationSuccessAction(): void
    {
        // Given
        $fixture = new DeviceAuthorizedFixture();
        $this->addFixture($fixture);
        $this->executeFixtures();

        /** @var ConnectedDevice $connectedDevice */
        $connectedDevice = $fixture->getReference(DeviceAuthorizedFixture::AUTHORIZED_DEVICE_REFERENCE, ConnectedDevice::class);

        /** @var Server $server */
        $server = $fixture->getReference(DeviceAuthorizedFixture::SERVER_REFERENCE, Server::class);

        $token = $this->encryptionService->createTrustedDeviceToken($connectedDevice);

        // When
        $response = $this->request($server->getHost()->getDomain(), '/', '127.0.0.1', $token);

        // Then
        $this->em->refresh($server);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals(1, count($server->getConnectedDevices()));
        
    }

    public function testRequestPairingAction(): void
    {
        self::markTestSkipped();

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
