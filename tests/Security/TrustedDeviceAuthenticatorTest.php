<?php

namespace App\Tests\Security;

use App\Security\TrustedDeviceAuthenticator;
use App\Tests\Builder\ApplicationEntityBuilder;
use App\Tests\Builder\HostEntityBuilder;
use App\Tests\Builder\ServerEntityBuilder;
use App\Tests\Builder\ServiceBuilder;
use App\Tests\Mock\ConnectedDeviceRepositoryMock;
use App\Tests\Mock\HostRepositoryMock;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class TrustedDeviceAuthenticatorTest extends TestCase
{
    private TrustedDeviceAuthenticator $trustedDeviceAuthenticator;

    private ?HostRepositoryMock $hostRepository = null;

    public function setUp(): void
    {
        $emMock = $this->createMock(EntityManagerInterface::class);
        $authorizationChecker = $this->createMock(AuthorizationChecker::class);

        $connectedDeviceRepository = new ConnectedDeviceRepositoryMock();
        $this->hostRepository = new HostRepositoryMock();

        $encryptionService = ServiceBuilder::getEncryptionService();
        $connectedDeviceManager = ServiceBuilder::getConnectedDeviceManager($connectedDeviceRepository, $encryptionService);
        $connectedDeviceFactory = ServiceBuilder::getConnectedDeviceFactory($encryptionService, $emMock);
        $appContext = ServiceBuilder::getAppContext($this->hostRepository);

        $this->trustedDeviceAuthenticator = new TrustedDeviceAuthenticator(
            '_trusted_device',
            $connectedDeviceManager,
            $authorizationChecker,
            $connectedDeviceFactory,
            $appContext,
            new NullLogger()
        );
    }

    public function testSettingUpCookieCreation(): void
    {
        $domain = 'granted.devyour.cloud';

        $app = ApplicationEntityBuilder::create()->build();
        $server = ServerEntityBuilder::create()->withPairing(true)->build();
        $host = HostEntityBuilder::create()
            ->withDomain($domain)
            ->withApp($app)
            ->withServer($server)
            ->build()
        ;

        $this->hostRepository->setHost($host);

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $host,
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->expectException(CustomUserMessageAuthenticationException::class);

        $result = $this->trustedDeviceAuthenticator->authenticate($request);
    }
}
