<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\DeviceAuthorizedFixture;
use App\DataFixtures\DeviceCanBeAddedFixture;
use App\DataFixtures\DeviceNotAuthorizedFixture;
use App\Entity\ConnectedDevice;
use App\Entity\Server;
use App\Service\ConnectedDeviceManager;
use App\Service\EncryptionService;
use App\Tests\FixtureAwareWebTestCase;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\HttpFoundation\Response;

class ExternalAuthControllerTest extends FixtureAwareWebTestCase
{
    private EncryptionService $encryptionService;

    private ConnectedDeviceManager $connectedDeviceManager;

    private string $trustedCookieName;

    public function setUp(): void
    {
        parent::setUp();

        $container = self::getContainer();

        $this->trustedCookieName = $container->getParameter('trusted_device_cookie_name');
        $this->encryptionService = $container->get(EncryptionService::class);
        $this->connectedDeviceManager = $container->get(ConnectedDeviceManager::class);
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

    public function testAllowCreatingANewDevice(): void
    {
        // Given
        $fixture = new DeviceCanBeAddedFixture();
        $this->addFixture($fixture);
        $this->executeFixtures();

        /** @var Server $server */
        $server = $fixture->getReference(DeviceCanBeAddedFixture::SERVER_REFERENCE, Server::class);

        // When
        $response = $this->request($server->getHost()->getDomain(), '/', '127.0.0.1');
        $crawler = $this->client->getCrawler();

        $node = $crawler->filter('.device-code')->text();

        // Then
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $this->em->refresh($server);

        self::assertEquals(1, $server->getConnectedDevices()->count());

        /** @var ConnectedDevice */
        $connectedDevice = $server->getConnectedDevices()->first();

        self::assertFalse($connectedDevice->isActive());
        self::assertNotEmpty($connectedDevice->getAccessCode());
        self::assertEquals($connectedDevice->getAccessCode(), $node);

        $cookie = $this->client->getCookieJar()->get($this->trustedCookieName);
        self::assertNotEmpty($cookie->getValue());

        // When
        $response = $this->request($server->getHost()->getDomain(), '/', '127.0.0.1', $cookie->getValue());

        // Then
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        // When
        $connectedDevice = $this->connectedDeviceManager->validateAccessCode($connectedDevice->getAccessCode());

        // Then
        self::assertTrue($connectedDevice->isActive());
        self::assertNull($connectedDevice->getAccessCode());
    }

    public function testWrongForwardedAuthRequest(): void
    {
        // When
        $this->client->request('GET', '/auth', [], [], []);
        $response = $this->client->getResponse();

        // Then
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    private function request(string $host, string $uri, string $ip, string $token = null): Response
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
                new BrowserKitCookie($this->trustedCookieName, \urlencode($token), (string) strtotime('+1 day'))
            );
        }

        $this->client->request('GET', '/auth', [], [], $params);

        return $this->client->getResponse();
    }
}
