<?php

namespace App\Tests\Security;

use App\Context\AppContext;
use App\Security\TrustedDeviceAuthenticator;
use App\Service\EncryptionService;
use App\Tests\Builder\ApplicationEntityBuilder;
use App\Tests\Builder\ConnectedDeviceEntityBuilder;
use App\Tests\Builder\HostEntityBuilder;
use App\Tests\Builder\ServerEntityBuilder;
use App\Tests\Builder\ServiceBuilder;
use App\Tests\Builder\UserEntityBuilder;
use App\Tests\Mock\ConnectedDeviceRepositoryMock;
use App\Tests\Mock\HostRepositoryMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TrustedDeviceAuthenticatorTest extends TestCase
{
    private TrustedDeviceAuthenticator $trustedDeviceAuthenticator;

    private ?HostRepositoryMock $hostRepository = null;

    private AppContext $appContext;

    private EncryptionService $encryptionService;

    private ConnectedDeviceRepositoryMock $connectedDeviceRepository;

    private AuthorizationCheckerInterface|MockObject $authorizationChecker;

    private string $trustedCookieName = '_trusted_device';

    public function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->connectedDeviceRepository = new ConnectedDeviceRepositoryMock();
        $this->hostRepository = new HostRepositoryMock();

        $this->encryptionService = ServiceBuilder::getEncryptionService();
        $connectedDeviceManager = ServiceBuilder::getConnectedDeviceManager($this->connectedDeviceRepository, $this->encryptionService);
        $this->appContext = ServiceBuilder::getAppContext($this->hostRepository);

        $this->trustedDeviceAuthenticator = new TrustedDeviceAuthenticator(
            $this->trustedCookieName,
            $connectedDeviceManager,
            $this->authorizationChecker,
            $this->appContext,
            new NullLogger()
        );
    }

    public function testSettingUpCookieCreation(): void
    {
        $domain = 'test.example.com';

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
        $this->expectExceptionMessage('[COOKIE AUTH] No trusted cookie, setting up for creation');

        $this->trustedDeviceAuthenticator->authenticate($request);
        self::assertTrue($this->appContext->hasValidForwardedAuthRequest());
        self::assertTrue($this->appContext->createTrustedCookie());
    }

    public function testWithExistingCookieButNoDeviceFound(): void
    {
        $domain = 'test.example.com';
        $deviceHash = 'TEST_HASH';

        $app = ApplicationEntityBuilder::create()->build();
        $server = ServerEntityBuilder::create()->withPairing(true)->build();
        $host = HostEntityBuilder::create()
            ->withDomain($domain)
            ->withApp($app)
            ->withServer($server)
            ->build()
        ;
        $connectedDevice = ConnectedDeviceEntityBuilder::create()
            ->withServer($server)
            ->withHash($deviceHash)
            ->build()
        ;

        $token = $this->encryptionService->createTrustedDeviceToken($connectedDevice);

        $this->hostRepository->setHost($host);

        $request = new Request(
            [], [], [],
            [
                $this->trustedCookieName => \urlencode($token),
            ],
            [], []
        );

        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $host,
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('[COOKIE AUTH] No device found');

        $this->trustedDeviceAuthenticator->authenticate($request);
        self::assertTrue($this->appContext->hasValidForwardedAuthRequest());
        self::assertTrue($this->appContext->createTrustedCookie());
    }

    public function testWithExistingCookieAndDeviceFound(): void
    {
        $domain = 'test.example.com';
        $deviceHash = 'TEST_HASH';

        $user = UserEntityBuilder::create()->build();
        $app = ApplicationEntityBuilder::create()->build();
        $server = ServerEntityBuilder::create()->withPairing(true)->withUser($user)->build();
        $host = HostEntityBuilder::create()
            ->withDomain($domain)
            ->withApp($app)
            ->withServer($server)
            ->build()
        ;
        $connectedDevice = ConnectedDeviceEntityBuilder::create()
            ->withServer($server)
            ->withHash($deviceHash)
            ->withUser($user)
            ->build()
        ;

        $token = $this->encryptionService->createTrustedDeviceToken($connectedDevice);

        $this->hostRepository->setHost($host);
        $this->connectedDeviceRepository->addDevice($connectedDevice);

        $request = new Request(
            [], [], [],
            [
                $this->trustedCookieName => \urlencode($token),
            ],
            [], []
        );

        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $host,
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->authorizationChecker->expects($this->any())->method('isGranted')->willReturn(true);

        $result = $this->trustedDeviceAuthenticator->authenticate($request);

        self::assertInstanceOf(SelfValidatingPassport::class, $result);
        self::assertTrue($this->appContext->hasValidForwardedAuthRequest());
        self::assertFalse($this->appContext->createTrustedCookie());
    }

    public function testWithHostNotFound(): void
    {
        $request = new Request(
            [], [], [], [], [], []
        );

        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'wrong.host.com',
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('[COOKIE AUTH] Initialization failed : Host not found: wrong.host.com');

        $this->trustedDeviceAuthenticator->authenticate($request);
    }
}
