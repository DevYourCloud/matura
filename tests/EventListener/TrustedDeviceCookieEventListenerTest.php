<?php

namespace App\Tests\EventListener;

use App\Context\AppContext;
use App\Entity\ConnectedDevice;
use App\EventListener\TrustedDeviceCookieEventListener;
use App\Model\ForwardedRequest;
use App\Service\EncryptionService;
use App\Tests\Builder\ApplicationEntityBuilder;
use App\Tests\Builder\ConnectedDeviceEntityBuilder;
use App\Tests\Builder\HostEntityBuilder;
use App\Tests\Builder\ServerEntityBuilder;
use App\Tests\Builder\ServiceBuilder;
use App\Tests\Mock\HostRepositoryMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class TrustedDeviceCookieEventListenerTest extends TestCase
{
    private EncryptionService $encryptionService;

    private TrustedDeviceCookieEventListener $trustedDeviceCookieListener;

    private HostRepositoryMock $hostRepository;

    private AppContext $appContext;

    public function setUp(): void
    {
        $this->hostRepository = new HostRepositoryMock();

        $this->encryptionService = ServiceBuilder::getEncryptionService('30');
        $this->appContext = ServiceBuilder::getAppContext($this->hostRepository);

        $this->trustedDeviceCookieListener = ServiceBuilder::getTrustedDeviceCookieListener($this->appContext, $this->encryptionService);
    }

    public function testCookieAddedOnRequested(): void
    {
        $domain = 'authorized.devyour.cloud';

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
            ->withHash('MY_HASH')
            ->build()
        ;

        $this->hostRepository->setHost($host);

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $domain,
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);

        $this->prepareAppContext($request, true, $connectedDevice);

        $responseEvent = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->createMock(Request::class),
            1,
            new Response()
        );

        $this->trustedDeviceCookieListener->__invoke($responseEvent);

        $response = $responseEvent->getResponse();

        self::assertCount(1, $response->headers->getCookies());
        $cookie = $response->headers->getCookies()[0];

        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertEquals($domain, $cookie->getDomain());
        self::assertTrue($cookie->isSecure());

        $cookieTime = new \DateTime();
        $cookieTime->setTimestamp($cookie->getExpiresTime());

        $expiryTime = new \DateTime();
        $expiryTime->add(new \DateInterval('P30D'));

        self::assertEquals($expiryTime->format('Y-m-d'), $cookieTime->format('Y-m-d'));

        $token = $this->encryptionService->decodeTrustedDeviceToken(\urldecode($cookie->getValue()));

        self::assertNotNull($token);
    }

    public function testNoCookieOnUnauthorizedAccess(): void
    {
        $domain = 'unauthorized.devyour.cloud';

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
            ->withHash('MY_HASH')
            ->build()
        ;

        $this->hostRepository->setHost($host);

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $domain,
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);
        $this->prepareAppContext($request, false, $connectedDevice);

        $responseEvent = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->createMock(Request::class),
            1,
            new Response()
        );

        $this->trustedDeviceCookieListener->__invoke($responseEvent);

        $response = $responseEvent->getResponse();
        self::assertCount(0, $response->headers->getCookies());
    }

    public function testNoCookieOnNotPairingServer(): void
    {
        $domain = 'unauthorized.devyour.cloud';

        $app = ApplicationEntityBuilder::create()->build();
        $server = ServerEntityBuilder::create()->withPairing(false)->build();
        $host = HostEntityBuilder::create()
            ->withDomain($domain)
            ->withApp($app)
            ->withServer($server)
            ->build()
        ;
        $connectedDevice = ConnectedDeviceEntityBuilder::create()
            ->withServer($server)
            ->withHash('MY_HASH')
            ->build()
        ;

        $this->hostRepository->setHost($host);

        $request = new Request([], [], [], [], [], []);
        $request->headers->add([
            'X-Forwarded-Method' => 'GET',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => $domain,
            'X-Forwarded-Uri' => '/',
            'X-Forwarded-For' => '100.111.222.333',
            'User-Agent' => 'Firefox',
        ]);
        $this->prepareAppContext($request, false, $connectedDevice);

        $responseEvent = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->createMock(Request::class),
            1,
            new Response()
        );

        $this->trustedDeviceCookieListener->__invoke($responseEvent);

        $response = $responseEvent->getResponse();
        self::assertCount(0, $response->headers->getCookies());
    }

    private function prepareAppContext(Request $request, bool $createCookie, ConnectedDevice $connectedDevice): void
    {
        $this->appContext->initializeFromRequest(new ForwardedRequest($request));
        $this->appContext->setCreateTrustedCookie($createCookie);
        $this->appContext->setConnectedDevice($connectedDevice);
    }
}
